/**
 * jQuery HTML Encode - HTML encodes the given text, returing a string suitable for setting as innerHTML
 *
 * Usage:
 * var html = $.htmlEncode(text);
 *
 * @version 1.0.1
 * @author Gwilym Evans <gwilym.evans@interspire.com>
 * @date 2009-10-06
 */

/**
* Changelog
*
* 2009-10-06 1.0.1
* - Minor fix to be compatible with some older jquery versions which have broken chaining in the .text() method
*/

/*
Copyright © 2008 Interspire Pty Ltd (referred to as
Interspire from here on in) - All Rights Reserved. THIS COPYRIGHT INFORMATION
MUST REMAIN INTACT AND MAY NOT BE MODIFIED IN ANY WAY.

Interspire Website Publisher is commercial software.

When you purchased this software you agreed to accept the terms of this
Agreement. This Agreement is a legal contract, which specifies the terms of the
license and warranty limitation between you and 'Interspire'. You should
carefully read the following terms and conditions before installing or using
this software. Unless you have a different license agreement obtained from
'Interspire', installation or use of this software indicates your acceptance of
the license and warranty limitation terms contained in this Agreement. If you do
not agree to the terms of this Agreement, promptly delete and destroy all copies
of the Software.

Versions of the Software: Only one licensed copy of Interspire Website Publisher may be
used on one web site. Each license allows for one (1) installation only.

License to Redistribute: Distributing the software and/or documentation with
other products (commercial or otherwise) by any means without prior written
permission from ' Interspire' is forbidden. All rights to the Interspire Website Publisher
software and documentation not expressly granted under this Agreement are
reserved to 'Interspire'.

Disclaimer of Warranty: THIS SOFTWARE AND ACCOMPANYING DOCUMENTATION ARE
PROVIDED "AS IS" AND WITHOUT WARRANTIES AS TO PERFORMANCE OF MERCHANTABILITY OR
ANY OTHER WARRANTIES WHETHER EXPRESSED OR IMPLIED. BECAUSE OF THE VARIOUS
HARDWARE AND SOFTWARE ENVIRONMENTS INTO WHICH Interspire Website Publisher MAY BE USED, NO
WARRANTY OF FITNESS FOR A PARTICULAR PURPOSE IS OFFERED. THE USER MUST ASSUME
THE ENTIRE RISK OF USING THIS PROGRAM. ANY LIABILITY OF 'Interspire' WILL BE
LIMITED EXCLUSIVELY TO PRODUCT REPLACEMENT OR REFUND OF PURCHASE PRICE. IN NO
CASE SHALL 'Interspire' BE LIABLE FOR ANY INCIDENTAL, SPECIAL OR CONSEQUENTIAL
DAMAGES OR LOSS, INCLUDING, WITHOUT LIMITATION, LOST PROFITS OR THE INABILITY
TO USE EQUIPMENT OR ACCESS DATA, WHETHER SUCH DAMAGES ARE BASED UPON A BREACH
OF EXPRESS OR IMPLIED WARRANTIES, BREACH OF CONTRACT, NEGLIGENCE, STRICT TORT,
OR ANY OTHER LEGAL THEORY. THIS IS TRUE EVEN IF 'Interspire' IS ADVISED OF THE
POSSIBILITY OF SUCH DAMAGES. IN NO CASE WILL 'Interpire' OR LIABILITY EXCEED THE
AMOUNT OF THE LICENSE FEE ACTUALLY PAID BY LICENSEE TO 'Interspire'.

Warning: This program is protected by copyright law. Unauthorized reproduction
or distribution of this program, or any portion of it, may result in severe
civil and criminal penalties, and will be prosecuted to the maximum extent
possible under the law.
*/

;(function($){
	var htmlEncodeDiv = null;

	$.htmlEncode = function (text) {
		if (text === null) {
			return null;
		}

		if (text === '') {
			return '';
		}

		if (htmlEncodeDiv === null) {
			htmlEncodeDiv = $('<div></div>');
		}

		htmlEncodeDiv.text(text);
		var html = htmlEncodeDiv.html();

		html = html.replace(/"/g, '&quot;');

		return html;
	}
})(jQuery);
