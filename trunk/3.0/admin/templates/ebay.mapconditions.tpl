{% import "macros/forms.tpl" as form %}

{{ form.startForm }}

{{ form.startRow([ 'label': 'New:', 'required': conditionRequired ]) }}
	{{ form.select('newCondition', conditions) }}
{{ form.endRow() }}

{{ form.startRow([ 'label': 'Used:', 'required': conditionRequired ]) }}
	{{ form.select('usedCondition', conditions) }}
{{ form.endRow() }}

{{ form.startRow([ 'label': 'Refurbished:', 'required': conditionRequired ]) }}
	{{ form.select('refurbishedCondition', conditions) }}
{{ form.endRow() }}

{{ form.endForm }}
