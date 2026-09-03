/**
 * doPost endpoint for Arc EOD Report.
 * Add this file to the project and deploy the Web App.
 */

function doPost(e) {
  const body = JSON.parse(e.postData.contents || '{}');

  if (body.api_key !== PropertiesService.getScriptProperties().getProperty('API_KEY')) {
    return jsonResponse_({ success: false, error: 'Invalid API key' });
  }

  const action = body.action;

  switch (action) {
    case 'submit':
      return jsonResponse_(submitEODFromWP_(body));
    case 'get_my_reports':
      return jsonResponse_(getMyReportsFromWP_(body));
    case 'get_stats':
      return jsonResponse_(getStatsFromWP_(body));
    default:
      return jsonResponse_({ success: false, error: 'Action not supported' });
  }
}

function jsonResponse_(payload) {
  return ContentService.createTextOutput(JSON.stringify(payload))
    .setMimeType(ContentService.MimeType.JSON);
}

function submitEODFromWP_(data) {
  const ss = obtenerHojaPrincipal_();
  const sh = ss.getSheetByName(CONFIG.REPORTS_SHEET_NAME);

  sh.appendRow([
    new Date(),                       // Submission Date
    data.report_date || '',           // Report Date
    data.wp_email || '',              // Team Member
    data.hours_worked || 0,           // Hours Worked (Total)
    '',                               // Time Summary
    data.work_description || '',      // Work Description
    data.shipped_today || '',         // Shipped Today
    data.in_progress || '',           // In Progress
    '',                               // Progress %
    '',                               // In Progress ETA
    '',                               // Should've Been Done But Isn't
    '',                               // Missed Reason
    '',                               // New ETA
    '',                               // Slipped / At Risk
    '',                               // Recommendations
    data.top_priorities || '',        // Top 3 Priorities for Tomorrow
    'Submitted',                      // Status
    new Date(),                       // Updated On
    Utilities.getUuid(),              // Report ID
    data.blockers || ''               // Blockers
  ]);

  return { success: true, message: 'Report saved' };
}

function getMyReportsFromWP_(data) {
  const ss = obtenerHojaPrincipal_();
  const sh = ss.getSheetByName(CONFIG.REPORTS_SHEET_NAME);
  const rows = sh.getDataRange().getValues().slice(1);
  const email = String(data.wp_email || '').toLowerCase().trim();

  const reports = rows.filter(function(r) {
    return String(r[2]).toLowerCase().trim() === email;
  }).map(function(r) {
    return {
      submissionDate: r[0],
      reportDate: r[1],
      teamMember: r[2],
      hoursWorked: r[3],
      workDescription: r[5],
      shippedToday: r[6],
      inProgress: r[7],
      topPriorities: r[15],
      status: r[16],
      reportId: r[18]
    };
  });

  return { success: true, reports: reports };
}

function getStatsFromWP_(data) {
  const ss = obtenerHojaPrincipal_();
  const sh = ss.getSheetByName(CONFIG.REPORTS_SHEET_NAME);
  const email = String(data.wp_email || '').toLowerCase().trim();
  const count = sh.getDataRange().getValues().slice(1).filter(function(r) {
    return String(r[2]).toLowerCase().trim() === email;
  }).length;

  return {
    success: true,
    week_hours: 0,
    eod_count: count,
    active_tasks: 0,
    candidates: 0
  };
}
