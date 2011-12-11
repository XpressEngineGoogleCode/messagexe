function completeGetStatistics(ret) {
	var month = getStatistics.month;
	var data = ret['data'];
	var html = '<tr><td>'+month+'</td><td>'+data.sms_sk_count+'</td><td>'+data.sms_kt_count+'</td><td>'+data.sms_lg_count+'</td>';
	html += '<td>'+data.lms_sk_count+'</td><td>'+data.lms_kt_count+'</td><td>'+data.lms_lg_count+'</td>';
	html += '<td>'+data.mms_sk_count+'</td><td>'+data.mms_kt_count+'</td><td>'+data.mms_lg_count+'</td>';
	html += '<td>'+data.oversea_count+'</td></tr>';
	jQuery('#summary').before(html);


	// sum
	getStatistics.sum_sms_sk += parseInt(data.sms_sk_count);
	getStatistics.sum_sms_kt += parseInt(data.sms_kt_count);
	getStatistics.sum_sms_lg += parseInt(data.sms_lg_count);
	getStatistics.sum_lms_sk += parseInt(data.lms_sk_count);
	getStatistics.sum_lms_kt += parseInt(data.lms_kt_count);
	getStatistics.sum_lms_lg += parseInt(data.lms_lg_count);
	getStatistics.sum_mms_sk += parseInt(data.mms_sk_count);
	getStatistics.sum_mms_kt += parseInt(data.mms_kt_count);
	getStatistics.sum_mms_lg += parseInt(data.mms_lg_count);
	getStatistics.sum_overesa += parseInt(data.oversea_count);
	// total
	var sum_sms = parseInt(data.sms_sk_count) + parseInt(data.sms_kt_count) + parseInt(data.sms_lg_count);
	var sum_lms = parseInt(data.lms_sk_count) + parseInt(data.lms_kt_count) + parseInt(data.lms_lg_count);
	var sum_mms = parseInt(data.mms_sk_count) + parseInt(data.mms_kt_count) + parseInt(data.mms_lg_count);
	getStatistics.total_sms_count += sum_sms;
	getStatistics.total_lms_count += sum_lms;
	getStatistics.total_mms_count += sum_mms;
	getStatistics.total_oversea_count += parseInt(data.oversea_count);

	jQuery('#sum_sms_sk').text(getStatistics.sum_sms_sk);
	jQuery('#sum_sms_kt').text(getStatistics.sum_sms_kt);
	jQuery('#sum_sms_lg').text(getStatistics.sum_sms_lg);
	jQuery('#sum_lms_sk').text(getStatistics.sum_lms_sk);
	jQuery('#sum_lms_kt').text(getStatistics.sum_lms_kt);
	jQuery('#sum_lms_lg').text(getStatistics.sum_lms_lg);
	jQuery('#sum_mms_sk').text(getStatistics.sum_mms_sk);
	jQuery('#sum_mms_kt').text(getStatistics.sum_mms_kt);
	jQuery('#sum_mms_lg').text(getStatistics.sum_mms_lg);
	jQuery('#sum_oversea').text(getStatistics.sum_oversea);

	jQuery('#total_sms_count').text(getStatistics.total_sms_count);
	jQuery('#total_lms_count').text(getStatistics.total_lms_count);
	jQuery('#total_mms_count').text(getStatistics.total_mms_count);
	jQuery('#total_oversea_count').text(getStatistics.total_oversea_count);

	getStatistics.month++;

	if (getStatistics.month<= 12) setTimeout("getStatistics()", 100);
}

function getStatistics() {
	if (typeof(getStatistics.month)=='undefined') getStatistics.month = 1;
	var stats_date = jQuery('#stats_date').val();
	exec_xml(
		'textmessage',
		'getTextmessageAdminStatisticsMonthly',
		{stats_date:stats_date,month:getStatistics.month},
		completeGetStatistics,
		['error','message','data']
	);
}

jQuery(function($) {
	getStatistics.sum_sms_sk = 0;
	getStatistics.sum_sms_kt = 0;
	getStatistics.sum_sms_lg = 0;
	getStatistics.sum_lms_sk = 0;
	getStatistics.sum_lms_kt = 0;
	getStatistics.sum_lms_lg = 0;
	getStatistics.sum_mms_sk = 0;
	getStatistics.sum_mms_kt = 0;
	getStatistics.sum_mms_lg = 0;
	getStatistics.sum_oversea = 0;

	getStatistics.total_sms_count = 0;
	getStatistics.total_lms_count = 0;
	getStatistics.total_mms_count = 0;
	getStatistics.total_oversea_count = 0;
	getStatistics();
});
