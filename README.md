# kanboard stuff :

- migration from taskboard

- list all tasks by due date

- import estimate & productions automatically exported from cadratin  
	( root ) loginctl enable-linger <username>
	( user )
	cp and edit inotify/cadratin.{path,service} to ~/config/systemd/user/  
	systemctl enable --now --user cadratin.path  
	./inotify/cadratin_inotify.sh  

- close old estimates  
	crontab -e  
		0	2	*	*	*	php index.php kanboard estimates_purge
