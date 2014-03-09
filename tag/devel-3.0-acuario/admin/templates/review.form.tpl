
	<script language=JavaScript>

		var checkLink = 0;

		function ConfirmCancel()
		{
			if(confirm('{% lang 'CancelEditReview' %}'))
			{
				document.location.href='index.php?ToDo=viewReviews';
			}
			else
			{
				return false;
			}
		}

		function CheckEditReviewForm()
		{
			// Make sure that all of the form variables, etc are valid
			var f = document.frmReview;

			if(f.revtitle.value.length == 0)
			{
				alert('{% lang 'ReviewEnterTitle' %}');
				f.revtitle.focus();
				f.revtitle.select();
				return false;
			}

			if(f.revtext.value.length == 0)
			{
				alert('{% lang 'ReviewEnter' %}');
				f.revtext.focus();
				f.revtext.select();
				return false;
			}

			// Everything is OK, return true
			return true;
		}

	</script>

	<form action="index.php?ToDo=editReview2" onsubmit="return ValidateForm(CheckEditReviewForm)" name="frmReview" method="post">
	<input type="hidden" name="reviewId" value="{{ ReviewId|safe }}">
	<div class="BodyContainer">
	<table class="OuterPanel">
	  <tr>
		<td class="Heading1" id="tdHeading">{% lang 'EditReview' %}</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{% lang 'EditReviewIntro' %}</p>
			<div><input type="submit" name="SaveButton1" value="{% lang 'Save' %}" class="FormButton">&nbsp; <input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()"></div>
		</td>
	  </tr>
		<tr>
			<td>
			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'ReviewDetails' %}</td>
				</tr>
					<tr>
						<td class="FieldLabel">
							<span class="Required">*</span>&nbsp;{% lang 'ReviewTitle' %}:
						</td>
						<td>
							<input type="text" name="revtitle" class="Field300" value="{{ Title|safe }}">
							<img onmouseout="HideHelp('d2');" onmouseover="ShowHelp('d2', '{% lang 'ReviewTitle' %}', '{% lang 'ReviewTitleHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
							<div style="display:none" id="d2"></div>
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							<span class="Required">*</span>&nbsp;{% lang 'Review' %}:
						</td>
						<td>
							<textarea name="revtext" rows="5" class="Field300">{{ Review|safe }}</textarea>
							<img onmouseout="HideHelp('d3');" onmouseover="ShowHelp('d3', '{% lang 'Review' %}', '{% lang 'ReviewHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
							<div style="display:none" id="d3"></div>
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							&nbsp;&nbsp;&nbsp;{% lang 'ReviewAuthor' %}:
						</td>
						<td>
							<input type="text" name="revfromname" class="Field300" value="{{ FromName|safe }}">
							<img onmouseout="HideHelp('d1');" onmouseover="ShowHelp('d1', '{% lang 'ReviewAuthor' %}', '{% lang 'ReviewAuthorHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
							<div style="display:none" id="d1"></div>
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							&nbsp;&nbsp;&nbsp;{% lang 'ReviewStatus' %}:
						</td>
						<td>
							<select name="revstatus" class="Field150">
								{{ StatusOptions|safe }}
							</select>
							<img onmouseout="HideHelp('d4');" onmouseover="ShowHelp('d4', '{% lang 'ReviewStatus' %}', '{% lang 'ReviewStatusHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
							<div style="display:none" id="d4"></div>
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							&nbsp;&nbsp;&nbsp;{% lang 'ReviewRating' %}:
						</td>
						<td>
							<select name="revrating" class="Field150">
								{{ RatingOptions|safe }}
							</select>
						</td>
					</tr>
					<tr>
						<td class="Gap">&nbsp;</td>
						<td class="Gap"><div><input type="submit" name="SaveButton1" value="{% lang 'Save' %}" class="FormButton">&nbsp; <input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()"></div>
						</td>
					</tr>
					<tr><td class="Gap"></td></tr>
			 </table>
			</td>
		</tr>
	</table>
	</div>
	</form>
