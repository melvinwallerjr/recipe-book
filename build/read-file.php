<?php
function hasLabel($str) {
	return (count(preg_split("/^[\-\w]+:/", $str)) > 1 || count(preg_split("/^\.\w+/", $str)) > 1);
}

function units($value) {
	if (!strpos($value,'" "')) {
		if (strpos($value,'" ') > -1) {
			$value = str_replace('" ', '" "', $value);
		}
		if (strpos($value,' "') > -1) {
			$value = str_replace(' "', '" "', $value);
		}
	}
	$value = str_replace('"', '', str_replace('" "', '###', $value)); // prepare for value split
	$value = explode('###', trim($value)); // return data
	if (!isset($value[2])) {
		$value[2] = $value[0];
	}
	return [ 0 => trim($value[0]), 1 => trim($value[2]), 2 => trim($value[1]) ];
}

function ab($value) {
	$value = str_replace('"', '', str_replace('" "', '###', $value)); // prepare for value split
	$value = explode('###', trim($value)); // split values
	if (!isset($value[2])) {
		$value[2] = '';
	}
	return $value;
}

function parseRecipe($fileName) {
	$fileContents = file_get_contents($fileName);
	$contents = preg_replace("/\r/", '', trim($fileContents)); // drop carriage returns
	$contents = explode("\n", $contents); // split on new line

	$recipe = []; // create recipe
	$instruction = []; // create instructions
	$stage = null;
	$process = [];
	$step = null;

	for ($i = 0; $i < count($contents); $i++) {
		$content = trim(preg_replace("/\s+/", ' ', $contents[$i])); // drop multiple spaces

		if (strlen(trim($content)) > 0) { // has content, no blank lines
			if (count(preg_split("/^[-\w]+:/", $content)) > 1) { // header items
				$ary = preg_split("/:\s/", $content);
				$label = strtolower(array_shift($ary));
				$value = trim(join(': ', $ary));
				$recipe[$label] = $value;

				if (count($contents) > $i + 1 && !hasLabel($contents[$i + 1])) { // text in next line(s)
					$copyright = '';
					for (; $i + 1 < count($contents); $i++) { // collect text
						if ($i + 1 > count($contents) || hasLabel($contents[$i + 1])) {
							break; // exit when label encountered
						}
						$copyright .= ' ' . $contents[$i + 1];
					}
					$recipe['copyright'] = trim($copyright);
				}
			} elseif (count(preg_split("/^\.\w+/", $content)) > 1) { // instruction items
				$ary = preg_split("/\s/", $content); // split label / value
				$label = strtolower(str_replace('.', '', array_shift($ary))); // pull off label
				$value = trim(join(' ', $ary)); // reduce white space
				$value = preg_replace("/(\\\\\()(\d)/", ' $2/', $value); // convert fractions

				// collect any details that follow a value
				$detail = '';
				if (count($contents) > $i && !hasLabel($contents[$i + 1])) { // text in next line(s)
					for (; $i + 1 < count($contents); $i++) { // collect text
						if ($i + 1 > count($contents) || hasLabel($contents[$i + 1])) {
							break; // exit when label encountered
						}
						$detail .= ' ' . $contents[$i + 1];
					}
				}
				$detail = trim($detail);

				// format the various data types
				if ($label === 'sh') { // stage name
					$value = ucwords(strtolower($value)); // force name case
				} elseif ($label === 'ig') { // recipe title
					$value = units($value);
				} elseif ($label === 'ab') { // recipe title
					$value = ab($value);
				} elseif ($label === 'ih') {
					$value = preg_replace("/\"/", '', $value);
				} elseif ($label === 'te') { // temperature
					$value = explode(' ', $value);
					if (!isset($value[2])) {
						$value[2] = '';
					}
				} elseif ($label === 'rz') { // recipe title
					$value = trim(str_replace('"', '', str_replace('" "', '###', $value))); // prepare for value split
					$value = explode('###', $value);
					$value[0] = ucwords(strtolower($value[0])); // force title case
				}

				if (in_array($label, ['ih', 'sh', 'ph', 'wr'])) { // instruction stages
					if (!empty($stage) && count($process) > 0) { // add previous process to recipe instruction
						array_push($instruction, [
							'stage' => $stage,
							'process' => $process
						]);
					}
					$stage = $label;
					$process = []; // start new process
				}

				if (empty($stage)) { // continue with header values
					if (strlen($detail) > 0) {
						$recipe[$label] = [
							'value' => $value,
							'detail' => preg_replace("/\s+/", ' ', $detail)
						];
					} else {
						$recipe[$label] = $value;
					}
				} else { // start instruction phase
					array_push($process, [$label => [
						'value' => $value,
						'detail' => $detail
					]]);
				}
			}
		}
	}

	if (!empty($stage) && count($process) > 0) { // add previous process to recipe instruction
		array_push($instruction, ['stage' => $stage, 'process' => $process]);
	}

	$recipe['instruction'] = $instruction;

	return $recipe;
}
