<?php
$start = microtime(true);

include('./build/read-file.php');
include('./build/read-dir.php');

// ensure recipe book json file has been generated
readDirectory('/recipe-book/build/recipe-list');
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1, user-scalable=yes">

	<title>Recipe Book</title>

	<link rel="stylesheet" href="media/reset.css">
	<link rel="stylesheet" href="media/style.css">
	<script src="media/vue.js"></script>
</head>
<body>

<header>
	<h1>Recipe Book</h1>
	<p id="recipe-count" hidden></p>
</header>

<main id="recipe-book">
	<article>
		<form id="search">
			<p>
				<label for="query">Search</label>
				<input id="query" name="query" v-model="searchQuery">
			</p>
		</form>
		<recipe-book
			:data="listData"
			:columns="listColumns"
			:filter-key="searchQuery"
		></recipe-book>
	</article>
</main>

<footer>
	<p>Total process time: <?php echo number_format(microtime(true) - $start, 4); ?> seconds.</p>
</footer>

<div class="modal-mask"></div>
<dialog id="recipe-card" class="modal-window">
	<recipe-card
		:data="cardData"
	></recipe-card>
	<div id="modal-close">X</div>
</dialog>

<div class="scroll-top" title="Scroll to Page Top">^</div>

<script type="text/x-template" id="book-template">
	<table v-if="filteredData.length">
		<thead>
		<tr>
			<th v-for="key in columns"
				@click="sortBy(key)"
				:class="{ active: sortKey === key }">
				{{ key | capitalize }}
				<span class="arrow" :class="sortOrders[key] > 0 ? 'asc' : 'dsc'"></span>
			</th>
		</tr>
		</thead>
		<tbody>
		<tr v-for="entry in filteredData" @click="loadModal(entry.index)">
			<td v-for="key in columns">
				<span v-if="key === 'date'">{{ entry[key] | date }}</span>
				<span v-else>{{ entry[key] }}</span>
			</td>
		</tr>
		</tbody>
	</table>
	<p v-else>No matches found.</p>
</script>

<script type="text/x-template" id="card-template">
	<article class="details imperial" v-if="loadData">
		<h2>{{ loadData['name'] }}</h2>
		<p class="units">Units: <a class="btn imperial" @click="setUnits('imperial')">Imperial</a> <a class="btn metric" @click="setUnits('metric')">Metric</a></p>
		<p>Posted: {{ loadData['date'] | date }}</p>
		<div v-for="(entry, key) in loadData['instruction']">
			<section v-if="entry.stage === 'ih'">
				<h3>Ingredients</h3>
				<p v-if="entry.process[0]['ih']">
					<em v-if="entry.process[0]['ih'] === 'string'">{{ entry.process[0]['ih'] | stripSlash }}</em>
					<em v-else>{{ entry.process[0]['ih'].value | stripSlash }}</em>
				</p>
				<p v-if="entry.process[0]['ih'] && entry.process[0]['ih'].detail">
					{{ entry.process[0]['ih'].detail | stripSlash }}
				</p>
				<ol>
					<template v-for="(task, lbl) in entry.process">
						<template v-for="(step, idx) in task" v-if="entry.stage !== idx">
							<li>
								<template v-if="step.value && typeof(step.value) !== 'string' && step.value[0] !== 'undefined'">
									<span class="imperial">{{ step.value[0] | stripSlash }} {{ step.value[2] | stripSlash }}</span>
									<span class="metric">{{ step.value[1] | stripSlash }} {{ step.value[2] | stripSlash }}</span>
								</template>
								<template v-else>{{ step.value | stripSlash }}</template>
								{{ step.detail | stripSlash }}
							</li>
						</template>
					</template>
				</ol>
			</section>
			<section v-if="entry.stage === 'ph'">
				<h3>Process</h3>
				<p v-if="entry.process[0]['ph']">
					<em v-if="entry.process[0]['ph'] === 'string'">{{ entry.process[0]['ph'] | stripSlash }}</em>
					<em v-else>{{ entry.process[0]['ph'].value | stripSlash }}</em>
				</p>
				<p v-if="entry.process[0]['ph'] && entry.process[0]['ph'].detail">
					{{ entry.process[0]['ph'].detail | stripSlash }}
				</p>
				<template v-for="task in entry.process">
					<template v-for="(step, idx) in task" v-if="entry.stage !== idx">
						<h4 style="margin-top:.5em;" v-if="step.value && typeof(step.value) === 'string'">Step {{ step.value | stripSlash }}</h4>
						<span v-if="step.value && typeof(step.value) !== 'string' && step.value[0] !== 'undefined'">
							<span class="imperial">{{ step.value[0] | stripSlash }}{{ step.value[2] | stripSlash }}</span>
							<span class="metric">{{ step.value[1] | stripSlash }}{{ step.value[2] | stripSlash }}</span>
						</span>
						<span v-else>{{ step.detail | stripSlash }}</span>
					</template>
				</template>
				<br><br>
			</section>
			<section v-if="entry.stage === 'sh'">
				<h3>Summary</h3>
				<p v-if="entry.process[0]['sh']">
					<em v-if="entry.process[0]['sh'] === 'string'">{{ entry.process[0]['sh'] | stripSlash }}</em>
					<em v-else>{{ entry.process[0]['sh'].value | stripSlash }}</em>
				</p>
				<p v-if="entry.process[0]['sh'] && entry.process[0]['sh'].detail">
					{{ entry.process[0]['sh'].detail | stripSlash }}
				</p>
				<template v-for="task in entry.process">
					<template v-for="(step, idx) in task" v-if="entry.stage !== idx">
						<p><strong>{{ step.value | stripSlash }}</strong> {{ step.detail | stripSlash }}</p>
					</template>
				</template>
			</section>
			<section v-if="entry.stage === 'wr'">
				<h3>Source</h3>
				<p v-if="entry.process[0]['wr']">
					<em v-if="entry.process[0]['wr'] === 'string'">{{ entry.process[0]['wr'] | stripSlash }}</em>
					<em v-else>{{ entry.process[0]['wr'].value | stripSlash }}</em>
				</p>
				<p v-if="entry.process[0]['wr'] && entry.process[0]['wr'].detail">
					{{ entry.process[0]['wr'].detail | stripSlash }}
				</p>
				<template v-for="task in entry.process">
					<template v-for="(step, idx) in task" v-if="entry.stage !== idx">
						<p><strong>{{ step.value | stripSlash }}</strong> {{ step.detail | stripSlash }}</p>
					</template>
				</template>
			</section>
		</div>
		<p v-if="loadData['copyright']"><small>{{ loadData['copyright'] }}</small></p>
	</article>
</script>

<script src="media/recipeBook.js"></script>

</body>
</html>
