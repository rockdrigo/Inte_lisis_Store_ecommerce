 <tr>
    <td class="FieldLabel">
        &nbsp;&nbsp;&nbsp;{{ IncludeTypeLabel|safe }}
    </td>
    <td>
        <label><input type="checkbox" id="include{{ IncludeType|safe }}" name="includeType[{{ IncludeType|safe }}]" value="1" {{ IncludeChecked|safe }}/>{{ YesIncludeType|safe }}</label>
    </td>
</tr>