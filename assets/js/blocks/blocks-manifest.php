<?php
// This file is generated. Do not modify it manually.
return array(
	'campaign-settings' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'fundrik/campaign-settings',
		'title' => 'Campaign Settings',
		'category' => 'fundrik',
		'icon' => 'admin-settings',
		'description' => 'Campaign settings. Not displayed on the site.',
		'supports' => array(
			'html' => false,
			'customClassName' => false,
			'inserter' => false,
			'multiple' => false,
			'renaming' => false,
			'reusable' => false,
			'lock' => false
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'move' => true,
					'remove' => true
				)
			)
		),
		'textdomain' => 'fundrik',
		'editorScript' => 'file:./index.js',
		'usesContext' => array(
			'postType'
		)
	),
	'campaign-summary' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'fundrik/campaign-summary',
		'title' => 'Campaign Summary',
		'category' => 'fundrik',
		'icon' => 'chart-bar',
		'description' => 'Displays campaign progress, collected amount, goal, and donation count for the current campaign.',
		'supports' => array(
			'html' => false
		),
		'textdomain' => 'fundrik',
		'editorScript' => 'file:./index.js',
		'usesContext' => array(
			'postType'
		),
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'donation-form' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'fundrik/donation-form',
		'title' => 'Donation Form',
		'category' => 'fundrik',
		'icon' => 'heart',
		'description' => 'Displays the donation form for the current campaign when the campaign accepts donations.',
		'supports' => array(
			'html' => false,
			'inserter' => false,
			'multiple' => false,
			'reusable' => false,
			'lock' => false
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'move' => false,
					'remove' => true
				)
			)
		),
		'textdomain' => 'fundrik',
		'editorScript' => 'file:./index.js',
		'usesContext' => array(
			'postType'
		),
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./view.js',
		'render' => 'file:./render.php'
	)
);
