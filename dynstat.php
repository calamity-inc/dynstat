<?php
if(!is_dir("build"))
{
	mkdir("build");
}

file_put_contents("build/.nojekyll", "");

file_put_contents(".dynstat_runtime.php", <<<'EOC'
<?php
$file = $argv[1];

$path = "/";
if($file != "index.php")
{
	$path .= $file;
	if(substr($path, -4) == ".php")
	{
		$path = substr($path, 0, -4);
	}
}

$_SERVER = [
	"REQUEST_URI" => $path,
];

require $file;
EOC);

foreach(scandir(".") as $file)
{
	if(is_dir($file)
		|| substr($file, 0, 1) == "."
		|| $file==basename(__FILE__)
		)
	{
		continue;
	}

	ob_start();

	passthru("php .dynstat_runtime.php ".$file);

	$out_name = "build/$file";
	if(substr($out_name, -4) == ".php")
	{
		$out_name = substr($out_name, 0, -4).".html";
	}
	file_put_contents($out_name, ob_get_contents());

	ob_end_clean();
}

unlink(".dynstat_runtime.php");
