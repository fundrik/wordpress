<?php
// This file is generated. Do not modify it manually.
return array(
	'campaign-settings' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'fundrik/campaign-settings',
		'title' => 'Campaign Settings',
		'category' => 'widgets',
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
	'donation-form' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'fundrik/donation-form',
		'title' => 'Donation Form',
		'category' => 'widgets',
		'icon' => 'heart',
		'description' => 'Donation form.',
		'supports' => array(
			'html' => false
		),
		'textdomain' => 'fundrik',
		'editorScript' => 'file:./index.js',
		'render' => 'file:./render.php'
	)
);
