
<div id="" class="WeightRanges">
	{% lang 'ByWeightRowStart' %} <input type="text" name="shipping_byweight[lower_{{ POS|safe }}]" value="{{ LOWER_VAL|safe }}" id="lower_{{ POS|safe }}" class="Field50 WeightRange LowerRange">
	{{ WeightMeasurement|safe }} {% lang 'ByWeightRowMiddle' %} <input type="text" name="shipping_byweight[upper_{{ POS|safe }}]" value="{{ UPPER_VAL|safe }}" id="upper_{{ POS|safe }}" class="Field50 WeightRange UpperRange">
	{{ WeightMeasurement|safe }} {% lang 'ByWeightRowEnd' %}
	{{ CurrencyTokenLeft|safe }} <input type="text" name="shipping_byweight[cost_{{ POS|safe }}]" value="{{ COST_VAL|safe }}" id="cost_{{ POS|safe }}" class="Field50 WeightRange RangeCost"> {{ CurrencyTokenRight|safe }}
	<a href="#" onclick="AddWeightRange(this.parentNode); return false;" class="add">Add</a>
	<a href="#" onclick="RemoveWeightRange(this.parentNode); return false;" class="remove">Remove</a>
</div>
