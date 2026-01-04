/**
 * Participants List
 * An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2025, Thorsten Ahlers
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

function imcgerPtslCountSign(e) {
	let	imcgerPtslMaxsign = this.getAttribute("maxlength");
	let	imcgerPtslNewtext = this.value.length + ' / ' + imcgerPtslMaxsign;

	document.getElementById("ptsl_count_comment").innerText = imcgerPtslNewtext;
}

document.getElementById("ptsl_comment").addEventListener("selectionchange", imcgerPtslCountSign);
document.getElementById("ptsl_comment").dispatchEvent(new Event("selectionchange"));
