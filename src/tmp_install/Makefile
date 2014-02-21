TARGET0 = /usr/local/tmp_swansoft_git/tmp_install

SUBDIRS = sql sbin
 
INSTALL0 = /usr/bin/install -m 755 -o root -g root $< $(TARGET0)


all:


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
	chown root:root $(TARGET0); \
	fi;


install: make_dir0	
	@$(INS_DIRS)