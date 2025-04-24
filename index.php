<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load the cities from the file "villes.json"
$cities = json_decode(file_get_contents("villes.json"), true);
$numVertices = count($cities);
$numEdges = 30;

function createGraph($cities, $numEdges) {
    $graph = [];
    foreach ($cities as $ville) {
        $graph[$ville] = [];
    }

    while ($numEdges > 0) {
        $city1 = $cities[array_rand($cities)];
        $city2 = $cities[array_rand($cities)];
        if ($city1 !== $city2 && !isset($graph[$city1][$city2])) {
            $distance = rand(10, 100);
            $graph[$city1][$city2] = $distance;
            $graph[$city2][$city1] = $distance;
            $numEdges--;
        }
    }

    return $graph;
}

function dijkstra($graph, $start, $end) {
    $distances = [];
    $previous = [];
    $unvisited = [];

    foreach ($graph as $city => $edges) {
        $distances[$city] = INF;
        $previous[$city] = null;
        $unvisited[$city] = true;
    }

    $distances[$start] = 0;

    while (!empty($unvisited)) {
        $current = null;
        foreach ($unvisited as $city => $isUnvisited) {
            if ($current === null || $distances[$city] < $distances[$current]) {
                $current = $city;
            }
        }

        if ($distances[$current] === INF) break;

        foreach ($graph[$current] as $neighbor => $distance) {
            $newDist = $distances[$current] + $distance;
            if ($newDist < $distances[$neighbor]) {
                $distances[$neighbor] = $newDist;
                $previous[$neighbor] = $current;
            }
        }

        unset($unvisited[$current]);
    }

    $path = [];
    for ($city = $end; $city !== null; $city = $previous[$city]) {
        array_unshift($path, $city);
    }

    return $path[0] === $start ? ['path' => $path, 'distance' => $distances[$end]] : ['path' => [], 'distance' => INF];
}

//  Detail of the path
function showPathDetails($path, $graph) {
    if (empty($path)) {
        echo "No path found.";
        return;
    }

    echo "Shortest route :<br>";
    $totalDistance = 0;
    for ($i = 0; $i < count($path) - 1; $i++) {
        $d = $graph[$path[$i]][$path[$i+1]];
        echo "{$path[$i]} → {$path[$i+1]} : $d km<br>";
        $totalDistance += $d;
    }
    echo "Total distance: $totalDistance km<br>";
}

// Distance table display
function createDistanceTable($graph, $cities) {
    echo "<table border='1'><tr><th>Ville</th>";
    foreach ($cities as $c) echo "<th>$c</th>";
    echo "</tr>";

    foreach ($cities as $c1) {
        echo "<tr><th>$c1</th>";
        foreach ($cities as $c2) {
            echo "<td>" . (isset($graph[$c1][$c2]) ? $graph[$c1][$c2] : "-") . "</td>";
        }
        echo "</tr>";
    }

    echo "</table>";
}

function plotGraph($graph, $path, $cities) {
    $w = 1000; $h = 800;
    $image = imagecreatetruecolor($w, $h);


    $bg = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 30, 30, 30);
    $red = imagecolorallocate($image, 255, 0, 0);
    $blue = imagecolorallocate($image, 0, 0, 160);
    $white = imagecolorallocate($image, 255, 255, 255);
    $gray = imagecolorallocate($image, 150, 150, 150);

    imagefilledrectangle($image, 0, 0, $w, $h, $bg);

    $radius = min($w, $h) / 2 - 80;
    $cx = $w / 2;
    $cy = $h / 2;
    $n = count($cities);
    $positions = [];

    foreach ($cities as $i => $city) {
        $angle = 2 * M_PI * $i / $n;
        $x = $cx + cos($angle) * $radius;
        $y = $cy + sin($angle) * $radius;
        $positions[$city] = [round($x), round($y)];
    }

    $pathEdges = [];
    for ($i = 0; $i < count($path) - 1; $i++) {
        $pathEdges[] = [$path[$i], $path[$i+1]];
    }

    foreach ($graph as $c1 => $edges) {
        foreach ($edges as $c2 => $dist) {
            if ($c1 < $c2) {
                $isPath = in_array([$c1, $c2], $pathEdges) || in_array([$c2, $c1], $pathEdges);
                $col = $isPath ? $red : $gray;

                imageline($image, $positions[$c1][0], $positions[$c1][1], $positions[$c2][0], $positions[$c2][1], $col);

                $mx = ($positions[$c1][0] + $positions[$c2][0]) / 2;
                $my = ($positions[$c1][1] + $positions[$c2][1]) / 2;
                $text = "$dist";

                imagefilledrectangle($image, (int)($mx - 10), (int)($my - 7), (int)($mx + 10), (int)($my + 7), $bg);
                imagestring($image, 2, (int)($mx - 6), (int)($my - 6), $text, $black);


            }
        }
    }

    foreach ($positions as $city => [$x, $y]) {
        imagefilledellipse($image, $x, $y, 12, 12, $black);
        imagestring($image, 4, $x + 10, $y - 7, $city, $black);
    }

    ob_start();
    imagepng($image);
    $imageData = ob_get_clean();
    imagedestroy($image);

    return $imageData;
}

// Main script
$graph = createGraph($cities, $numEdges);
$startCity = $cities[array_rand($cities)];
do {
    $endCity = $cities[array_rand($cities)];
} while ($endCity === $startCity);

$shortest = dijkstra($graph, $startCity, $endCity);
$path = $shortest['path'];

$imageData = plotGraph($graph, $path, $cities);
$imageSrc = 'data:image/png;base64,' . base64_encode($imageData);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Shortest route</title>
</head>
<body>
<h2>Path between <?php echo $startCity; ?> & <?php echo $endCity; ?></h2>
<?php showPathDetails($path, $graph); ?>
<h2>Table of distances</h2>
<?php createDistanceTable($graph, $cities); ?>
<h2>Network graph</h2>
<img src="<?php echo $imageSrc; ?>" alt="Graph">
</body>
</html>
