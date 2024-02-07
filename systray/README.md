Useful SystemTray specification v.1

Problems of existing Linux system trays - overhead and overengeenering.
But current solution is easy in use, easy in implementation.

Main feature of current tray specification - is using unix principle "everything is file". 

File processing is implemented in every programming language and easy for understanding.

So general idea of specification - application, which wants to be trayed (client) - just creating few records in some filesystem zone, and use it to contact with application, which is handle tray (host).

We are using /run directory which is part of tmpfs and already have folder attached to current user.

CLIENT, which wants to be trayed - should create folder named with it's PID under /run/user/UID/systray folder.

For example, let it be `/run/user/1000/systray/12345`

In this folder, CLIENT should place few files:

`title` - plain file with application title

`tooltip` - plain file with tooltip which will be displayed during hover

`icon_name` - plain file with name of icon from current theme.

`icon_pixbuf` - raw icon in pixbuf format, which will be described below.

`action` - file which will be used for sending signals to application

`.updated` - some empty file, modification date of what, will be used to update states.

After creating these files, CLIENT should go in loop, and keep finger on modification date of `/run/user/1000/systray/12345/.updated` file.

Once file modification time was changed - CLIENT should read file "action" and handle state according to command in this file.

For now I'm using two commands, which overlaping 95% of user's system tray requrements. It's command "Activate", which displaying main application's window on top.

And it's command "ContextMenu" which drawing simple popup menu near pointer.

So, to call context menu of app, there are enough to run `echo "ContextMenu" > /run/user/1000/systray/12345/action;touch /run/user/1000/systray/12345/.updated`

If CLIENT wants to update system tray icon, text, or something else - it should change modification date of the same /run/user/1000/systray/12345/.updated, and HOST should detect and act.

HOST, which wants to manage tray - should scan folder `/run/user/1000/systray/` for PIDs.

Good idea will be check if current PID is exist in system, and ignore/remove application's icon from tray, if PID doesn't exist anymore.

The simplest way to check if PID is exist in system, for programming languages which doesn't support process interface - just checking existence of /proc/PID folder. If it doesn't exist - application is dead.

So first way - scan /run/user/1000/systray/ and ignore+remove PIDs which doesn't exist in /proc filesystem.

Next, HOST application should step by step load existing directories (named as PID) - and draw information in tray zone, with any look&feel as it wants:

Read title, tooltip, and icon. Draw icon at tray zone, add tooltip to it, and connect pointer signals for click on icon according to programming language or framework, with simple actions for each click.

Main click(usually it's left mouse button) should call writing word "Activate" to `/run/user/UID/systray/PID/action` of icon what was clicked.

Secondary click(right mouse button) should call writing word "ContextMenu" to `/run/user/UID/systray/PID/action` of icon what was clicked.

After process of click, modify time of `/run/user/UID/systray/PID/.updated` should be update.

In loop, HOST application should scan `/run/user/UID/systray/` for new PIDs and for modify date of .updated file of each PID.

If file was modified comparing to previous check - then act. Re-draw icon, re-load title and so on.

Two examples are at folder. For now you can't run them because they are using my modified version of php-gtk3, but some later I also will put there examples on C and Python.
