# kanboard stuff :

- migration from taskboard

- list all tasks by due date

- import estimate automatically exported from cadratin  
	( root ) loginctl enable-linger <username>
	( user )
	cp and edit inotify/cadratin.{path,service} to ~/config/systemd/user/  
	systemctl enable --now --user cadratin.path  
