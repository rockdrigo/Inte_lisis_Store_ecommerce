<?php
/**
* Valid input types are checkbox OR text
*
*/

$settings = array(
		"uniqueSettingID" => array(
								"label" => "A checkbox setting",
								"input" => array(
												"type" => "checkbox",
												"label" => "A label displayed to the right of the checkbox",
												"checked" => true //is this checkbox ticked by default?
												),
								"help" => "This is a help tip displayed to the right of the setting"
								),
	 	"anotherSetting" => array(
							"label" => "A textbox setting",
							"required" => true, 						//optional, does this setting require a value?
							"input" => array(
											"type" => "text",
											"value" => "default value", //optional, the default value for the textbox
											"maxlength" => 25, 			//optional, max characters for the textbox
											"width"	=> 50				//optional, width in pixels for the textbox
											),
							"help" => "How to use the textbox setting",
							"validatejs" =>
									"
									if ($('#anotherSetting').val().length == 0) {
										alert('This setting requires a value');
										$('#anotherSetting').focus();
										return false;
									}
									"  // Optional, a javascript snippet to validate the setting. Return false for validation failure.
							)
		);

return $settings;
?>