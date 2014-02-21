TARGET0 = /usr/local/tmp_swansoft_git/lib/process

SUBDIRS = exception
INC_SRC0 = test_process.php. sw_abstract.class.php. sw_test.class.php. sw_swdata.class.php.
 
INSTALL0 = /usr/bin/install -m 644 -o swan -g swan $< $(TARGET0)


all:
test_process.php.: test_process.php
	$(INSTALL0)
sw_abstract.class.php.: sw_abstract.class.php
	$(INSTALL0)
sw_test.class.php.: sw_test.class.php
	$(INSTALL0)
sw_swdata.class.php.: sw_swdata.class.php
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