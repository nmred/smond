TARGET0 = /usr/local/tmp_swansoft_git/

SUBDIRS = sf tpl etc web inc lib shell tmp_install
INC_SRC0 = global.func.php. core.php. index.php.
 
INSTALL0 = /usr/bin/install -m 644 -o swan -g swan $< $(TARGET0)


all:
global.func.php.: global.func.php
	$(INSTALL0)
core.php.: core.php
	$(INSTALL0)
index.php.: index.php
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