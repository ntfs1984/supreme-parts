#!/system/php/bin/php
<?php
global $old_mtime, $mtime, $window;

function add_me_to_tray($my_name, $my_icon, $tooltip, $pixbuf=true) {
	global $mtime;
// Если директории в формате /run/user/CURRENT_USER/systray/PID не существует, то создаем ее
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
	global $old_mtime, $mtime, $context_menu;
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
		if (get_action()=="ContextMenu") { // Создаем простейшее контекстное меню. Реализовано в форме окна, поэтому не закрывается по потере фокуса
			$display = new GdkDisplay();
			global $x,$y;
			$x = $display->get_mouse_positionX(); // Возле курсора мыши, само собой
			$y = $display->get_mouse_positionY();
			unset($display);
			$context_menu = new GtkWindow();
			$context_menu->set_type_hint(2);
			$context_menu->set_size_request(200, 200);
			$context_menu->set_decorated(false);
			$vbox = new GtkBox(GtkOrientation::VERTICAL);
			$vbox->set_border_width(0);
			$o_button = GtkButton::new_with_label("Open"); // Менюшка Open будет открывать наше окно
			$o_button->connect("clicked", function() {global $x,$y, $window, $context_menu;$time=time();$window->show();$window->present_with_time($time);$window->activate_focus();$context_menu->destroy();});
			$vbox->add($o_button);
			$o_button->show();
			$button = GtkButton::new_with_label("Close"); // Менюшка Close будет его закрывать.
			$button->connect("clicked", function() {Gtk::main_quit();});
			$vbox->add($button);
			$button->show();
			$context_menu->add($vbox);
			$context_menu->show();
			$vbox->show();
			$context_menu->move($x+22,$y+22); // Не на иконке же рисовать меню - нарисуем чуть правее и ниже
			$context_menu->activate_focus();
		}
	}
	clearstatcache(); // Пых кеширует date modify по умолчанию, надо чистить
}
Gtk::init();
$window = new GtkWindow();
$window->set_size_request(250, 250);
$window->set_title("SystemTrayExaple");
$window->set_decorated(true);
$vbox = new GtkBox(GtkOrientation::VERTICAL);
$vbox->set_border_width(1);
$label = new GtkLabel("Ну типа контент");
$vbox->add($label);
$button = GtkButton::new_with_label("Кнобка !");
$vbox->add($button);
$window->add($vbox);
$window->connect("destroy", function() {
	Gtk::main_quit();
});
$window->show_all();
// Добавляем нашу программку в трей
add_me_to_tray("Test App", "viber", "Test App в трее", false);
// Добавляем таймер для периодического чека
Gtk::timeout_add(150, function () {global $window; check_for_actions($window);return true;});
$window->set_visible(true);
Gtk::main();
