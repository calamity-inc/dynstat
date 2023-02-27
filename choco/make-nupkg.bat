del "src\dynstat.php"
copy "..\dynstat.php" "src\dynstat.php" /a
cd src
choco pack
cd ..
for /r "src" %%x in (*.nupkg) do move "%%x" .
