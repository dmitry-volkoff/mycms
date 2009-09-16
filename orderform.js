$(document).ready(function()
{
$("#divsel").change(function() {
$("#" + this.value).show('fast').siblings().hide('fast');
});
});
