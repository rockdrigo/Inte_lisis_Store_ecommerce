<?php
/**
* Example field array for an export file type.
*
* Available formats are:
* number - Formats the number as defined in the template. Used for price fields.
* date - Formats a date field
* text - Strips html tags and decodes entities; removes excess white space; replaces new lines with spaces. *
*
* Labels for fields are defined in their language file (export.filetype.[typename].ini):
* fieldid = "label"
*
* Sub-fields would require custom row handling
*/

$fields = array(
            "uniqueFieldID"     => array(
            							"dbfield" 	=> "thedatabasefield",
            							"format"	=> "number|date|text"
            							),
			"field2"			=> array(
										"help" => "Optional help tip display to the right of the field header box",
										"toString" => "[subfield1]x [subfield2] - [subfield3]", // defines the format of combining subfields into one field for some export methods (eg. CSV)
										// an array of subfields this field has. eg products in an order
										"fields" => array(
    													"subfield1"	=> array("label" => "Sub Field 1"),
    													"subfield2"	=> array("label" => "Sub Field 2"),
    													"subfield3"	=> array("label" => "Sub Field 3", "format" => "number") // subfields can have formats
    											)
										)
        );

return $fields;
?>