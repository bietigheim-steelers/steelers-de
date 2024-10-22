[<img alt="Deployed with FTP Deploy Action" src="https://img.shields.io/badge/Deployed With-FTP DEPLOY ACTION-%3CCOLOR%3E?style=for-the-badge&color=2b9348">](https://github.com/SamKirkland/FTP-Deploy-Action)

# steelers-de-theme

## development mit DevilBox

### Installation

#### DevilBox

* siehe Contao Dokumentation: https://docs.contao.org/manual/en/guides/local-installation/devilbox/
* contao über den contao manager oder CLI installieren

#### steelers.de

* get a database dump with only data, not the structure.
* get the complete `/files/` folder.
* sync the content of this repo into the data folder `data/www/steelers/` of DevilBox
* rename `parameters.yml.example` to `parameters.yml` in `config` folder
* conntent to DevilBox run `shell.bat` (or `shell.sh`) from DevilBox folder
* run `updateTheme.sh` in `/src/` folder on the devilbox
* replace complete database with database from database dump

### Usage

start devilbox (in devilbox folder)
```Shell
docker-compose up -d httpd php mysql
```

stop devilbox (in devilbox folder)
```Shell
docker-compose stop
docker-compose rm -f
```
