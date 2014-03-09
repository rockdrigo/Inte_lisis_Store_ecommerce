
<div id="" class="TotalRanges">
	{% lang 'ByTotalRowStart' %} {{ CurrencyTokenLeft|safe }} <input type="text" name="shipping_bytotal[lower_{{ POS|safe }}]" value="{{ LOWER_VAL|safe }}" id="lower_{{ POS|safe }}" class="Field50 TotalRange LowerRange"> {{ CurrencyTokenRight|safe }}
	{{ TotalMeasurement|safe }} {% lang 'ByTotalRowMiddle' %} {{ CurrencyTokenLeft|safe }} <input type="text" name="shipping_bytotal[upper_{{ POS|safe }}]" value="{{ UPPER_VAL|safe }}" id="upper_{{ POS|safe }}" class="Field50 TotalRange UpperRange"> {{ CurrencyTokenRight|safe }}
	{{ TotalMeasurement|safe }} {% lang 'ByTotalRowEnd' %}
	{{ CurrencyTokenLeft|safe }} <input type="text" name="shipping_bytotal[cost_{{ POS|safe }}]" value="{{ COST_VAL|safe }}" id="cost_{{ POS|safe }}" class="Field50 TotalRange RangeCost"> {{ CurrencyTokenRight|safe }}
	<a href="#" onclick="AddTotalRange(this.parentNode); return false;" class="add">Add</a>
	<a href="#" onclick="RemoveTotalRange(this.parentNode); return false;" class="remove">Remove</a>
</div>
