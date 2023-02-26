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
	$minify_engine = file_get_contents("https://raw.githubusercontent.com/mecha-cms/x.minify/1276b3bf8a0de02fd49e01664c81b71178f3401c/engine/plug/minify.php");
	$minify_engine = substr($minify_engine, 0, strpos($minify_engine, "\$state = "));
	file_put_contents("minify_engine.php", $minify_engine);
	require "minify_engine.php";
	unlink("minify_engine.php");
}

echo "Making static buid...\n";
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
