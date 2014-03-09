
<div id="" class="VolumeRanges">
	{% lang 'ByVolumeRowStart' %} <input type="text" name="shipping_byvolume[lower_{{ POS|safe }}]" value="{{ LOWER_VAL|safe }}" id="lower_{{ POS|safe }}" class="Field50 VolumeRange LowerRange">
	{{ VolumeMeasurement|safe }} {% lang 'ByVolumeRowMiddle' %} <input type="text" name="shipping_byvolume[upper_{{ POS|safe }}]" value="{{ UPPER_VAL|safe }}" id="upper_{{ POS|safe }}" class="Field50 VolumeRange UpperRange">
	{{ VolumeMeasurement|safe }} {% lang 'ByVolumeRowEnd' %}
	{{ CurrencyTokenLeft|safe }} <input type="text" name="shipping_byvolume[cost_{{ POS|safe }}]" value="{{ COST_VAL|safe }}" id="cost_{{ POS|safe }}" class="Field50 VolumeRange RangeCost"> {{ CurrencyTokenRight|safe }}
	<a href="#" onclick="AddVolumeRange(this.parentNode); return false;" class="add">Add</a>
	<a href="#" onclick="RemoveVolumeRange(this.parentNode); return false;" class="remove">Remove</a>
</div>
