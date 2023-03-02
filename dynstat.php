<?php
// Load config
$config = [];
if (is_file(".dynstat.json"))
{
	$config = json_decode(file_get_contents(".dynstat.json"), true);
}

// Set config defaults
$config["minify"] ??= false;
$config["skip_empty"] ??= false;

// Setup minify engine if enabled
if ($config["minify"])
{
	echo "Initialising minify engine...\n";
	$minify_engine = file_get_contents("https://raw.githubusercontent.com/mecha-cms/x.minify/1cfc21f10a4f323b904afd9123e95c90379ffd28/engine/plug/minify.php");
	$minify_engine = substr($minify_engine, 0, strpos($minify_engine, "\$state = "));
	file_put_contents("minify_engine.php", $minify_engine);
	require "minify_engine.php";
	unlink("minify_engine.php");
}

// Discover name of php binary
$php = "php";
if (defined("PHP_WINDOWS_VERSION_MAJOR"))
{
	$php = '"'.explode("\n", shell_exec("where php"))[0].'"';
}

echo "Making static buid...\n";
if(is_dir("build"))
{
	function rmr($file)
	{
		if(is_dir($file))
		{
			foreach(scandir($file) as $f)
			{
				if (substr($f, 0, 1) != ".")
				{
					rmr($file."/".$f);
				}
			}
			rmdir($file);
		}
		else
		{
			unlink($file);
		}
	}
	foreach(scandir("build") as $file)
	{
		if (substr($file, 0, 1) != ".")
		{
			rmr("build/".$file);
		}
	}
}
else
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

	passthru("$php .dynstat_runtime.php ".$file);

	$out_name = "build/$file";
	if(substr($out_name, -4) == ".php")
	{
		$out_name = substr($out_name, 0, -4).".html";
	}

	$contents = ob_get_contents();
	ob_end_clean();
	if($contents == "")
	{
		if ($config["skip_empty"])
		{
			continue;
		}
	}
	else if($config["minify"])
	{
		if(substr($out_name, -5) == ".html")
		{
			$contents = x\minify\f\minify_html($contents);
		}
		else if(substr($out_name, -4) == ".css")
		{
			$contents = x\minify\f\minify_css($contents);
		}
		else if(substr($out_name, -3) == ".js")
		{
			$contents = x\minify\f\minify_js($contents);
		}
		else if(substr($out_name, -5) == ".json")
		{
			$contents = x\minify\f\minify_json($contents);
		}
	}
	file_put_contents($out_name, $contents);
}

unlink(".dynstat_runtime.php");
