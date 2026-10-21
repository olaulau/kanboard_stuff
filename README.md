# kanboard stuff


# tech
- PHP 8.3 (8.4 WiP)


## features
- list all tasks by due date

- import estimate & productions automatically exported from cadratin  
	( root ) loginctl enable-linger <username>
	( user )
	cp and edit inotify/cadratin.{path,service} to ~/config/systemd/user/  
	systemctl enable --now --user cadratin.path  
	./inotify/cadratin_inotify.sh  

- close old estimates  
	mkdir -p tmp/log/
	crontab -e  
		0	2	*	*	*	cd kanboard_stuff/; (date && php index.php kanboard estimates_purge) 2>&1 >> tmp/log/estimates_purge.log
