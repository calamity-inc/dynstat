<?php
// Load config
$config = [];
if (is_file(".dynstat.json"))
{
	$config = json_decode(file_get_contents(".dynstat.json"), true);
}

// Set per-build config defaults
$config["dirs"] ??= ["."];
$config["minify"] ??= false;
$config["skip_empty"] ??= false;
$config["nojekyll"] ??= true;

// Store per-build config
$per_build_config_defaults = $config;
unset($per_build_config_defaults["php_ext"]);
unset($per_build_config_defaults["builds"]);

// Set global config defaults
$config["php_ext"] ??= [".php"];

// Configure builds
if (empty($config["builds"]))
{
	$config["builds"] = [
		"build" => $per_build_config_defaults
	];
}
else
{
	foreach($config["builds"] as &$bconf)
	{
		foreach($per_build_config_defaults as $k => $v)
		{
			$bconf[$k] ??= $v;
		}
	}
}

function isTrueForAnyBuild($key)
{
	global $config;
	foreach($config["builds"] as &$bconf)
	{
		if ($bconf[$key])
		{
			return true;
		}
	}
	return false;
}

// Setup minify engine if enabled
if (isTrueForAnyBuild("minify"))
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

function removePhpExtension($file)
{
	global $config;
	foreach ($config["php_ext"] as $ext)
	{
		if (substr($file, -strlen($ext)) == $ext)
		{
			return substr($file, 0, -strlen($ext));
		}
	}
	return null;
}

function resolveOutputPrefix($subdir)
{
	while (substr($subdir, 0, 2) == "./")
	{
		$subdir = substr($subdir, 2);
	}
	if ($subdir == ".")
	{
		return "";
	}
	if (substr($subdir, -1) != "/")
	{
		$subdir .= "/";
	}
	return $subdir;
}

foreach($config["builds"] as $bname => &$bconf)
{
	echo "Making ".$bname."...\n";

	if(is_dir($bname))
	{
		foreach(scandir($bname) as $file)
		{
			if (substr($file, 0, 1) != ".")
			{
				rmr($bname."/".$file);
			}
		}
	}
	else
	{
		mkdir($bname);
	}

	if ($config["nojekyll"])
	{
		file_put_contents($bname."/.nojekyll", "");
	}

	file_put_contents(".dynstat_runtime.php", str_replace("__BUILD_NAME__", $bname, <<<'EOC'
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

$_DYNSTAT = [
	"BUILD_NAME" => "__BUILD_NAME__",
];

$_SERVER = [
	"REQUEST_URI" => $path,
];

require $file;
EOC));

	foreach($bconf["dirs"] as $dir)
	{
		$prefix = resolveOutputPrefix($dir);
		if($prefix)
		{
			mkdir($bname."/".$prefix);
		}
		foreach(scandir($dir) as $file)
		{
			if(is_dir($dir."/".$file)
				|| substr($file, 0, 1) == "."
				|| $file==basename(__FILE__)
				)
			{
				continue;
			}

			$name = removePhpExtension($file);
			if ($name !== null) // Is a PHP file?
			{
				$out_name = "$bname/$prefix$name.html";
				ob_start();
				passthru("$php .dynstat_runtime.php ".$dir."/".$file);
				$contents = ob_get_contents();
				ob_end_clean();
			}
			else
			{
				$out_name = "$bname/$prefix$file";
				$contents = file_get_contents($dir."/".$file);
			}

			if($contents == "")
			{
				if ($bconf["skip_empty"])
				{
					continue;
				}
			}
			else if($bconf["minify"])
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
	}
}

unlink(".dynstat_runtime.php");
