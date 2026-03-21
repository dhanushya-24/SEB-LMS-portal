<?php
include "config.php";
header("Content-Type: application/json");

$code     = $_POST["code"]     ?? "";
$language = strtolower(trim($_POST["language"] ?? "python"));
$qid      = intval($_POST["qid"] ?? 0);

if (trim($code) === "") {
    echo json_encode(["error" => "Please write your code first.", "tests" => []]);
    exit;
}
if ($qid <= 0) {
    echo json_encode(["error" => "Invalid question ID.", "tests" => []]);
    exit;
}
if (!function_exists('shell_exec')) {
    echo json_encode(["error" => "shell_exec is disabled. Remove it from disable_functions in php.ini.", "tests" => []]);
    exit;
}

$tempDir = __DIR__ . DIRECTORY_SEPARATOR . "temp" . DIRECTORY_SEPARATOR;
if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);

$tcResult = mysqli_query($conn, "SELECT * FROM testcases WHERE question_id=$qid ORDER BY id ASC");
if (!$tcResult) {
    echo json_encode(["error" => "DB error: " . mysqli_error($conn), "tests" => []]);
    exit;
}

$tests = []; $allPassed = true;
while ($tc = mysqli_fetch_assoc($tcResult)) {
    $rawInput = $tc['input'] ?? '';
    $expected = trim($tc['expected_output']);
    $realInput = stripcslashes($rawInput);
    $result = executeLocally($language, $code, $realInput, $tempDir);
    if (isset($result['fatal'])) {
        echo json_encode(["error" => $result['fatal'], "tests" => []]);
        exit;
    }
    $output = trim($result['output']);
    $pass   = ($output === trim($expected));
    if (!$pass) $allPassed = false;
    $tests[] = ["input"=>$rawInput,"expected"=>$expected,"got"=>$output,"pass"=>$pass];
}
if (empty($tests)) {
    echo json_encode(["error" => "No test cases found for this question.", "tests" => []]);
    exit;
}
echo json_encode(["tests"=>$tests,"all_passed"=>$allPassed]);

function executeLocally(string $lang, string $code, string $stdin, string $tmpDir): array {
    $id = uniqid("lms_", true);
    if ($lang === 'python') {
        $src = $tmpDir.$id.".py"; $in = $tmpDir.$id.".stdin";
        file_put_contents($src,$code); file_put_contents($in,$stdin);
        $out = shell_exec("python \"$src\" < \"$in\" 2>&1");
        if ($out===null||stripos($out??'','is not recognized')!==false)
            $out = shell_exec("python3 \"$src\" < \"$in\" 2>&1");
        @unlink($src); @unlink($in);
        if ($out===null) return ['fatal'=>"Python not found. Install Python and add to PATH."];
        return ['output'=>$out];
    }
    if ($lang==='c') {
        $src=$tmpDir.$id.".c"; $bin=$tmpDir.$id.".exe"; $in=$tmpDir.$id.".stdin";
        file_put_contents($src,$code); file_put_contents($in,$stdin);
        $ce=shell_exec("gcc \"$src\" -o \"$bin\" -lm 2>&1");
        if ($ce===null){@unlink($src);return ['fatal'=>"GCC not found. Install MinGW: https://www.mingw-w64.org/"];}
        if (!file_exists($bin)){@unlink($src);return ['output'=>"Compile Error:\n".$ce];}
        $out=shell_exec("\"$bin\" < \"$in\" 2>&1");
        @unlink($src);@unlink($bin);@unlink($in);
        return ['output'=>$out??"(no output)"];
    }
    if ($lang==='cpp') {
        $src=$tmpDir.$id.".cpp"; $bin=$tmpDir.$id.".exe"; $in=$tmpDir.$id.".stdin";
        file_put_contents($src,$code); file_put_contents($in,$stdin);
        $ce=shell_exec("g++ -std=c++17 \"$src\" -o \"$bin\" -lm 2>&1");
        if ($ce===null){@unlink($src);return ['fatal'=>"G++ not found. Install MinGW: https://www.mingw-w64.org/"];}
        if (!file_exists($bin)){@unlink($src);return ['output'=>"Compile Error:\n".$ce];}
        $out=shell_exec("\"$bin\" < \"$in\" 2>&1");
        @unlink($src);@unlink($bin);@unlink($in);
        return ['output'=>$out??"(no output)"];
    }
    if ($lang==='java') {
        $dir=$tmpDir.$id.DIRECTORY_SEPARATOR; $src=$dir."Main.java"; $in=$tmpDir.$id.".stdin";
        mkdir($dir,0755,true);
        file_put_contents($src,$code); file_put_contents($in,$stdin);
        $ce=shell_exec("javac \"$src\" 2>&1");
        if ($ce===null){cleanDir($dir);@unlink($in);return ['fatal'=>"Java not found. Install JDK: https://adoptium.net/"];}
        if (!file_exists($dir."Main.class")){cleanDir($dir);@unlink($in);return ['output'=>"Compile Error:\n".$ce];}
        $out=shell_exec("java -cp \"$dir\" Main < \"$in\" 2>&1");
        cleanDir($dir);@unlink($in);
        return ['output'=>$out??"(no output)"];
    }
    return ['fatal'=>"Unsupported language: $lang"];
}
function cleanDir(string $d):void{if(!is_dir($d))return;foreach(glob($d."*")as $f){is_dir($f)?cleanDir($f.DIRECTORY_SEPARATOR):@unlink($f);}@rmdir($d);}
