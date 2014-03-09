
	<form enctype="multipart/form-data" action="index.php?ToDo={{ FormAction|safe }}" onsubmit="return ValidateForm(CheckNewsForm)" id="frmNews" method="post">
	<input type="hidden" name="newsId" value="{{ NewsId|safe }}">
	<div class="BodyContainer">
	<table class="OuterPanel">
	  <tr>
		<td class="Heading1" id="tdHeading">{{ Title|safe }}</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{{ Intro|safe }}</p>
			{{ Message|safe }}
			<p><input type="submit" name="SubmitButton1" value="{% lang 'Save' %}" class="FormButton">&nbsp; <input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()"></p>
		</td>
	  </tr>
		<tr>
			<td>
			  <table class="Panel">
			  	<tr>
				  <td class="Heading2" colspan=2>{% lang 'NewNewsDetails' %}</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						<span class="Required">*</span>&nbsp;{% lang 'NewsTitle' %}:
					</td>
					<td>
						<input type="text" id="newstitle" name="newstitle" class="Field400" value="{{ NewsTitle|safe }}">
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'SearchKeywords' %}:
					</td>
					<td>
						<input type="text" id="newssearchkeywords" name="newssearchkeywords" class="Field400" value="{{ NewsSearchKeywords|safe }}">
						<img onmouseout="HideHelp('searchkeywords');" onmouseover="ShowHelp('searchkeywords', '{% lang 'SearchKeywords' %}', '{% lang 'SearchKeywordsHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="searchkeywords"></div>
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'Visible' %}:
					</td>
					<td>
						<input type="checkbox" id="newsvisible" name="newsvisible" value="1" {{ NewsVisible|safe }}> <label for="newsvisible">{% lang 'YesNewsVisible' %}</label>
					</td>
				</tr>
				<tr>
					<td colspan="2" class="Gap"></td>
				</tr>
				<tr>
					<td colspan="2" class="Gap"></td>
				</tr>
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'PostContent' %}</td>
				</tr>
				<tr>
					<td colspan="2" style="padding-top:5px">
						{{ WYSIWYG|safe }}
					</td>
				</tr>
				<tr>
					<td class="Gap" colspan="2"><input type="submit" name="SubmitButton1" value="{% lang 'Save' %}" class="FormButton">&nbsp; <input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()">
					</td>
				</tr>
				<tr><td class="Gap"></td></tr>
				<tr><td class="Gap"></td></tr>
				<tr><td class="Sep" colspan="2"></td></tr>
			 </table>
			</td>
		</tr>
	</table>

	</div>
	</form>

	<script type="text/javascript">

		function ConfirmCancel()
		{
			if(confirm("{% lang 'ConfirmCancelNews' %}"))
				document.location.href = "index.php?ToDo=viewNews";
		}

		function CheckNewsForm()
		{
			var title = g("newstitle");

			if(g("wysiwyg"))
				var wysiwyg = g("wysiwyg"); // Text area
			else
				var wysiwyg = g("wysiwyg_html"); // DevEdit

			if(IsWysiwygEditorEmpty(wysiwyg.value))
			{
				alert("{% lang 'EnterNewsContent' %}");
				return false;
			}

			if(title.value == "")
			{
				alert("{% lang 'EnterNewsTitle' %}");
				title.focus();
				return false;
			}

			// Everything is OK
			return true;
		}

	</script>
