<?php
global $tray_box, $old_time, $mtime;
function rmrf($dir) {
   $files = array_diff(scandir($dir), array('.','..'));
    foreach ($files as $file) {
      (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
  }
function check_tray()
{
global $tray_box, $old_time, $mtime, $item_button;
// Сперва получаем список всех директорий, и определяем какая из них отсутствует в /proc, чтобы прибить трей
$scan = scandir($_SERVER['XDG_RUNTIME_DIR']."/systray/");
	foreach ($scan as $process) {
		if (is_numeric($process)) {
			if (is_dir("/proc/$process")) { // Процесс все еще существует, можем смотреть, было ли изменено состояние
			$old_mtime[$process] = $mtime[$process];
			$mtime[$process] = filectime($_SERVER['XDG_RUNTIME_DIR']."/systray/".$process."/.updated");	
			if ($mtime[$process]!=$old_mtime[$process]) { // И только если наше гостевое приложение обновило свои данные в системном трее - тогда обновляем и мы
				if (is_file($_SERVER['XDG_RUNTIME_DIR']."/systray/".$process."/icon_name")) {$icon = file_get_contents($_SERVER['XDG_RUNTIME_DIR']."/systray/".$process."/icon_name");}
				if (is_file($_SERVER['XDG_RUNTIME_DIR']."/systray/".$process."/title")) {$title = file_get_contents($_SERVER['XDG_RUNTIME_DIR']."/systray/".$process."/title");}
				if (is_file($_SERVER['XDG_RUNTIME_DIR']."/systray/".$process."/tooltip")) {$tooltip = file_get_contents($_SERVER['XDG_RUNTIME_DIR']."/systray/".$process."/tooltip");}
				if (!isset($item_button[$process])) { // Если процесс новенький, то создаем под него кнопку
					$item_button[$process] = new GtkButton();
					$item_button[$process]->connect("button-press-event",function($button, $event) {process_click($button, $event);});
				} else { // Если процесс уже есть в трее, то удаляем внутренности кнопки, оставляя все остальное. GObject не умеет в удаление событий, а это уменьшит утечку памяти
					foreach ($item_button[$process]->get_children() as &$value) {
						$value->destroy();
					}	
				}
				$item_box[$process] = new GtkBox(GtkOrientation::HORIZONTAL);
				$image = GtkImage::new_from_icon_name("$icon", 5);
				$image->set_pixel_size(22);
				$item_box[$process]->add($image);
				$image->show();
				$item_button[$process]->add($item_box[$process]);
				$item_button[$process]->set_name($process);
				$item_button[$process]->set_relief(GtkReliefStyle::NONE);
				$item_button[$process]->set_has_tooltip(true);
				$item_button[$process]->set_tooltip_text($tooltip);
				$tray_box->add($item_button[$process]);
				$item_box[$process]->show();
				$item_button[$process]->show();
				$tray_box->show();
			}
			} else { // Процесс более не найден - прибиваем его
			rmrf($_SERVER['XDG_RUNTIME_DIR']."/systray/".$process);
			$item_button[$process]->destroy();
			}	
		}
	}	
}
function process_click($item, $event) {
	$process = $item->get_name();
	if ($event->button->button == 1) {file_put_contents($_SERVER['XDG_RUNTIME_DIR']."/systray/".$process."/action", "Activate");} // Нажали левую кнопку - записали Activate
	if ($event->button->button == 3) {file_put_contents($_SERVER['XDG_RUNTIME_DIR']."/systray/".$process."/action", "ContextMenu");} // Нажали правую - записали ContextMenu
	touch($_SERVER['XDG_RUNTIME_DIR']."/systray/".$process."/.updated");
	return false;
}
Gtk::init();
function GtkWindowDestroy($widget=NULL, $event=NULL)
{
	Gtk::main_quit();
}
if (!is_dir($_SERVER['XDG_RUNTIME_DIR']."/systray")) {mkdir($_SERVER['XDG_RUNTIME_DIR']."/systray",0700);} // Если мы запустились первыми в системе, и директории нет - создаем
$win = new GtkWindow();
$win->set_default_size(300, 200);
$win->connect("destroy", "GtkWindowDestroy");
$tray_box = new GtkBox(GtkOrientation::HORIZONTAL);
$win->add($tray_box);
$win->show_all();
Gtk::timeout_add(150, function () {check_tray();return true;}); // Держим палец на трее каждые 150 мс, используя ГыТыКа таймаут. Без паники, процессорное время тратится разве что на ls и stat.
Gtk::main();
