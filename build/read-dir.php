<?php
function readDirectory($directory) {
	$baseDir = $_SERVER['DOCUMENT_ROOT']; // site root directory path
	$myFile = $baseDir . '/recipe-book/media/recipeBook.json';

	if (!is_file($myFile)) {
		$recipeBook = [];

		if ($dir = @scandir($baseDir . $directory)) { // open a specified directory
			$index = 0;

			foreach ($dir as $file) { // ignore files with dots
				if (!strstr($file, '.') && is_file($baseDir . $directory . '/' . $file)) {
					$recipe = parseRecipe($baseDir . $directory . '/' . $file);
					$recipe['name'] = empty($recipe['rz']['value'][0]) ?
						str_replace('RECIPE: ', '', $recipe['subject']) : $recipe['rz']['value'][0];
					$recipe['file'] = $file;
					$recipe['index'] = $index++;
					array_push($recipeBook, $recipe);
				}
			}
		}
		$handle = fopen($myFile, 'w') or die('Cannot open file:  ' . $myFile);

		if ($handle) {
			fwrite($handle, json_encode($recipeBook));
			fclose($handle);
		}
	}
}
