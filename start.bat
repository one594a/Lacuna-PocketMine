@echo off
chcp 65001 >nul
TITLE PocketMine server software (PHP 8.2) for Minecraft: Pocket Edition
cd /d %~dp0

set PHP_BINARY=
set PHP_ARGS=

if exist bin\php\php.exe (
	set PHPRC=%~dp0bin\php
	set PHP_BINARY=bin\php\php.exe
	set PHP_ARGS=-c "%~dp0bin\php"
) else (
	set PHP_BINARY=php
)

if exist Genisys.phar (
	set POCKETMINE_FILE=Genisys.phar
) else (
   if exist PocketMine-MP.phar (
	set POCKETMINE_FILE=PocketMine-MP.phar
	) else (
	 if exist PocketMine-Soft.phar (
	   set POCKETMINE_FILE=PocketMine-Soft.phar
	    ) else (
	      if exist src\pocketmine\PocketMine.php (
	       set POCKETMINE_FILE=src\pocketmine\PocketMine.php
	         ) else (
		    if exist Genisys*.phar (
			set POCKETMINE_FILE=Genisys*.phar
			   ) else (
		echo "Couldn't find a valid Genisys installation"
		pause
		exit 8
	)
	)
	)
	)
)

echo Starting server with %PHP_BINARY%
echo Type "stop" and press Enter to stop the server
echo.
"%PHP_BINARY%" %PHP_ARGS% "%POCKETMINE_FILE%" --enable-ansi %*