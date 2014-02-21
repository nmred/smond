TARGET0 = /usr/local/tmp_swansoft_git/etc/

SUBDIRS = php
INC_SRC0 = swan_soft.ini. sw_init_config. sw_cron.cf. sw_phpd.ini. rsyslogd.conf. sw_swdata.ini. redis.conf. my.cnf.
 
INSTALL0 = /usr/bin/install -m 644 -o swan -g swan $< $(TARGET0)


all:
swan_soft.ini.: swan_soft.ini
	$(INSTALL0)
sw_init_config.: sw_init_config
	$(INSTALL0)
sw_cron.cf.: sw_cron.cf
	$(INSTALL0)
sw_phpd.ini.: sw_phpd.ini
	$(INSTALL0)
rsyslogd.conf.: rsyslogd.conf
	$(INSTALL0)
sw_swdata.ini.: sw_swdata.ini
	$(INSTALL0)
redis.conf.: redis.conf
	$(INSTALL0)
my.cnf.: my.cnf
	$(INSTALL0)


INS_DIRS = \
	if test "$(SUBDIRS)"; then \
		echo "Install Dirs:$(SUBDIRS)"; \
		for i in `echo $(SUBDIRS)`; do \
			make install -C $$i; \
		done; \
	fi; 

make_dir0:
	-@if test ! -d $(TARGET0); then \
	echo "Make Dir:  $(TARGET0)"; \
	mkdir -m 755 $(TARGET0); \
	chown swan:swan $(TARGET0); \
	fi;


install: make_dir0 $(INC_SRC0) 	
	@$(INS_DIRS)