#!/system/php/bin/php
<?php
global $old_mtime, $mtime, $window;

function add_me_to_tray($my_name, $my_icon, $tooltip, $pixbuf=true) {
	global $mtime;
// Если директории в формате /run/user/CURRENT_USER/PID/systray не существует, то создаем ее
	if (!is_dir($_SERVER['XDG_RUNTIME_DIR']."/systray/".getmypid())) {
		mkdir($_SERVER['XDG_RUNTIME_DIR']."/systray/".getmypid(),0700,true);
	}
	if ($pixbuf==true) {
		file_put_contents($_SERVER['XDG_RUNTIME_DIR']."/systray/".getmypid()."/icon_pixbuf", $my_icon);
	} else {
		file_put_contents($_SERVER['XDG_RUNTIME_DIR']."/systray/".getmypid()."/icon_name", $my_icon);
	}
	touch($_SERVER['XDG_RUNTIME_DIR']."/systray/".getmypid()."/.updated");
	file_put_contents($_SERVER['XDG_RUNTIME_DIR']."/systray/".getmypid()."/title", $my_name);
	file_put_contents($_SERVER['XDG_RUNTIME_DIR']."/systray/".getmypid()."/tooltip", $tooltip);
	$mtime = filectime($_SERVER['XDG_RUNTIME_DIR']."/systray/".getmypid()."/.updated");
}

function get_action() {
	if (!is_file($_SERVER['XDG_RUNTIME_DIR']."/systray/".getmypid()."/action")) {return false;}
	$action=file_get_contents($_SERVER['XDG_RUNTIME_DIR']."/systray/".getmypid()."/action");
	return trim($action);
}
function check_for_actions($window)
{
	global $old_mtime, $mtime, $context_menu, $menu, $vbox;
	$old_mtime = $mtime;	
	$mtime = filectime($_SERVER['XDG_RUNTIME_DIR']."/systray/".getmypid()."/.updated");
	if ($mtime!=$old_mtime) { // Если файл изменился - значит произошло событие
		if (get_action()=="Activate") { // И это событие - активация
			// Все последующее - для активации окна на передний план
			$window->set_visible(true);
			$window->show();
			$window->activate_focus();
			$window->present_with_time($time);
			$window->show();
		}
		if (get_action()=="ContextMenu") { // Создаем простейшее контекстное меню
	    $menu->popup_at_pointer();
		}
	}
	clearstatcache(); // Пых кеширует date modify по умолчанию, надо чистить
}
Gtk::init();
$window = new GtkWindow();
$window->set_size_request(500, 500);
$vbox = new GtkBox(GtkOrientation::VERTICAL);
$menubar = new GtkMenuBar();
$vbox->pack_start($menubar, FALSE, FALSE, 0);
$menu = new GtkMenu();
$menu->append($menu_item=GtkMenuItem::new_with_label("Open"));
$menu->append($menu_item=GtkMenuItem::new_with_label("Show stats"));
$menu->append($menu_item=GtkMenuItem::new_with_label("Free memory"));
$menu->append($menu_item=GtkMenuItem::new_with_label("Quit monitor"));
$menuitem = new GtkMenuItem();
$menuitem->set_submenu($menu); // set the sub menu on menuitem
$menubar->append($menuitem); // append menu About to menubar
$menu->show_all();
$vbox->pack_start(new GtkLabel(""), TRUE, TRUE, 10);
$window->add($vbox);
$window->connect("destroy", function() {
	Gtk::main_quit();
});
$window->show_all();
$window->hide();
// Добавляем нашу программку в трей
add_me_to_tray("Test App", "viber", "Test App в трее", false);
// Добавляем таймер для периодического чека
Gtk::timeout_add(150, function () {global $window; check_for_actions($window);return true;});
$window->set_visible(true);
Gtk::main();
