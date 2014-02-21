TARGET0 = /usr/local/tmp_swansoft_git/inc/locale/zh_CN/LC_MESSAGES/

SUBDIRS = 
INC_SRC0 = swan_translater.mo. swan_translater.po.
 
INSTALL0 = /usr/bin/install -m 644 -o swan -g swan $< $(TARGET0)


all:
swan_translater.mo.: swan_translater.mo
	$(INSTALL0)
swan_translater.po.: swan_translater.po
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