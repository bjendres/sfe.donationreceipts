VERSION := $(shell head -1 NEWS)
DATE := $(shell date '+%Y-%m-%d')

dist:
	sed -i 's/<version>[^<]\+/<version>$(VERSION)/;s/<releaseDate>[^<]\+/<releaseDate>$(DATE)/' info.xml
	(echo; git ls-files)|sed 's!^!sfe.donationreceipts/!'|( cd .. && xargs zip donationreceipts-$(VERSION).zip)
