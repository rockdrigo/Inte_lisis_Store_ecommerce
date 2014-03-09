/**
 * Password Strength Meter (PCI)
 * updates meter and guidance text as user types in password
 * also provides a validate method for form to execute before submit
 *
 * How to use? add the following code to a template file
	<input type="password" autocomplete="off" id="userpass" name="userpass" value="">
	<div class="PasswordStrengthMeter" id="meterid"></div>
	<small class="note PasswordStrengthTip" id="tipid"></small>
	<script type="text/javascript" src="../javascript/passwordmeter.js"></script>
	<script type="text/javascript">
		lang.PasswordStrengthMeter_MsgDefault = "{% jslang 'PasswordStrengthMeter_MsgDefault' %}";
		lang.PasswordStrengthMeter_MsgTooShort = "{% jslang 'PasswordStrengthMeter_MsgTooShort' %}";
		lang.PasswordStrengthMeter_MsgNoAlphaNum = "{% jslang 'PasswordStrengthMeter_MsgNoAlphaNum' %}";
		lang.PasswordStrengthMeter_MsgWeak = "{% jslang 'PasswordStrengthMeter_MsgWeak' %}";
		lang.PasswordStrengthMeter_MsgStrong = "{% jslang 'PasswordStrengthMeter_MsgStrong' %}";
		lang.PasswordStrengthMeter_MsgVeryStrong = "{% jslang 'PasswordStrengthMeter_MsgVeryStrong' %}";
		lang.PasswordStrengthMeter_Tip = "{% jslang 'PasswordStrengthMeter_Tip' %}";
		var meter = new PasswordStrengthMeter('userpass', 'meterid', 'tipid', {{ PCIPasswordMinLen }});
		$(document).ready(function() {
			// init
			meter.init();

			// validation
			var res = meter.validate('a password');
			if (res.valid == false) {
				alert(res.msg);
			}
		});
	</script>
 * then, assign minLen right before displaying template
	$this->template->assign('PCIPasswordMinLen', GetConfig('PCIPasswordMinLen'));
 */
function PasswordStrengthMeter(targetid, elemid, tipid, minLen)
{
	this.targetid = targetid;
	this.target = $('#' + targetid);
	this.elemid = elemid;
	this.elem = $('#' + elemid);
	this.tipid = tipid;
	this.tip = $('#' + tipid);
	this.options = {
		minLen: minLen,
		colorMap: ['transparent', '#f33', '#ff6', '#cf6', '#063']
	};
}

PasswordStrengthMeter.prototype = {

	init: function()
	{
		if (this.options.minLen == 0) {
			// disabled
			return;
		}

		var self = this;
		this.target.css('float', 'left');
		this.populate();
		this.updateMeter(-1);

		this.target.bind('keyup', function() {
			if (this.value == '') {
				// reset
				self.updateMeter(-1);
			} else {
				var score = self.calculateScore(this.value);
				self.updateMeter(score);
			}
		});

		this.target.bind('focusin', function() {
			if (this.value != '') {
				// already has a value
				$(this).trigger('keyup');
			}

			self.elem.css('display', 'block');
			if (self.tip) {
				self.tip.css('display', 'block');
			}
		});

		this.target.bind('focusout', function() {
			if (this.value == '') {
				self.elem.css('display', 'none');
				if (self.tip) {
					self.tip.css('display', 'none');
				}
			}
		});
	},

	// called by other scripts to validate password string
	validate: function(pwd)
	{
		var res = {
			valid: true,
			msg: ''
		};

		if (this.options.minLen == 0) {
			// disabled
			return res;
		}

		var score = this.calculateScore(pwd);
		if (score == 0) {
			// too short
			res.valid = false;
			res.msg = lang.PasswordStrengthMeter_MsgTooShort.replace(':minLen', this.options.minLen);
		} else if (score == 1) {
			// no alpha or no numeric
			res.valid = false;
			res.msg = lang.PasswordStrengthMeter_MsgNoAlphaNum;
		}

		return res;
	},

	// populate this Meter
	populate: function()
	{
		var html = '<div class="pswGuide"></div>';
		html += '<div class="pswMeterTag">';
		html += '  <div class="pswMeter1"></div>';
		html += '  <div class="pswMeter2"></div>';
		html += '  <div class="pswMeter3"></div>';
		html += '  <div class="pswMeter4"></div>';
		html += '</div>';

		if (this.tip) {
			var tipMsg = lang.PasswordStrengthMeter_Tip.replace(':minLen', this.options.minLen);
			this.tip.html(tipMsg);
		}

		this.elem.html(html);
	},

	// update the password guide text and set color for box 1 to 4
	// use -1 score to reset meter to initial state
	updateMeter: function(score)
	{
		var guide = $('#' + this.elemid + ' .pswGuide');
		switch (score) {
			case 0:
				// increment score by 1 to show first red box
				score = 1;
				guide.html(lang.PasswordStrengthMeter_MsgTooShort.replace(':minLen', this.options.minLen));
			break;
			case 1:
				guide.html(lang.PasswordStrengthMeter_MsgNoAlphaNum);
			break;
			case 2:
				guide.html(lang.PasswordStrengthMeter_MsgWeak);
			break;
			case 3:
				guide.html(lang.PasswordStrengthMeter_MsgStrong);
			break;
			case 4:
				guide.html(lang.PasswordStrengthMeter_MsgVeryStrong);
			break;
			default:
				guide.html(lang.PasswordStrengthMeter_MsgDefault);
			break;
		}

		$('#' + this.elemid + ' .pswMeterTag div').css('backgroundColor', this.options.colorMap[0]);
		for (var i = score; i > 0; i--) {
			$('#' + this.elemid + ' .pswMeter' + i).css('backgroundColor', this.options.colorMap[score]);
		}
	},

	// simple password strength calculation (score 0 to 4)
	// 0 and 1 are invalid, 2 to 4 are 'weak' to 'very strong'
	calculateScore: function(pwd)
	{
		if (!pwd || pwd.length < this.options.minLen) {
			// at least minLen characters
			return 0;
		}

		// at least 1 alpha
		var alpha = pwd.match(/[a-zA-Z]/g);
		if (alpha == null) {
			return 1;
		}

		// and at least 1 numeric
		var numeric = pwd.match(/[0-9]/g);
		if (numeric == null) {
			return 1;
		}

		// if they get here, they deserve at least a weak score of 2
		var score = 2;
		var lowercase = pwd.match(/[a-z]/g);
		var uppercase = pwd.match(/[A-Z]/g);
		var other  = pwd.match(/[\W_]/g);
		if (lowercase != null && uppercase !== null) {
			// contains both lowercase and uppercase char
			score++;
		}

		if (other != null) {
			// contains non alphanumeric, or underscore char
			score++;
		}

		return score;
	}
};
