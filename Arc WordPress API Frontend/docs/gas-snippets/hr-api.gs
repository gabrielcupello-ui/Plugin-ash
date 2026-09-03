/**
 * Endpoint doPost para Arc Human Resources.
 * Añade este archivo al proyecto y despliega la Web App.
 */

function doPost(e) {
  const body = JSON.parse(e.postData.contents || '{}');

  if (body.api_key !== PropertiesService.getScriptProperties().getProperty('API_KEY')) {
    return jsonResponse_({ success: false, error: 'API key inválida' });
  }

  const action = body.action;

  switch (action) {
    case 'submit_application':
      return jsonResponse_(submitApplicationFromWP_(body));
    case 'get_interviews':
      return jsonResponse_(getInterviewsFromWP_(body));
    case 'get_stats':
      return jsonResponse_(getStatsFromWP_(body));
    default:
      return jsonResponse_({ success: false, error: 'Acción no soportada' });
  }
}

function jsonResponse_(payload) {
  return ContentService.createTextOutput(JSON.stringify(payload))
    .setMimeType(ContentService.MimeType.JSON);
}

function submitApplicationFromWP_(data) {
  const ss = obtenerHojaPrincipal_();
  const sh = ss.getSheetByName(CONFIG.APPLICATIONS_SHEET_NAME);

  sh.appendRow([
    new Date(),
    data.first_name || '',
    data.last_name || '',
    data.wp_email || '',
    data.phone || '',
    data.years_experience || '',
    data.english_level || '',
    data.positions_worked || '',
    data.domain_experience || '',
    data.accounting_software || '',
    data.excel_level || '',
    data.summary || '',
    '', // Audio URL
    '', // CV URL
    'New',
    new Date()
  ]);

  return { success: true, message: 'Solicitud recibida' };
}

function getInterviewsFromWP_(data) {
  const ss = obtenerHojaPrincipal_();
  const sh = ss.getSheetByName(CONFIG.INTERVIEWS_SHEET_NAME);
  const email = String(data.wp_email || '').toLowerCase().trim();
  const rows = sh.getDataRange().getValues().slice(1).filter(function(r) {
    return String(r[1]).toLowerCase().trim() === email;
  }).map(function(r) {
    return {
      scheduledOn: r[0],
      email: r[1],
      fullName: r[2],
      dateTime: r[3],
      location: r[4],
      interviewer: r[5],
      status: r[7]
    };
  });
  return { success: true, interviews: rows };
}

function getStatsFromWP_(data) {
  const ss = obtenerHojaPrincipal_();
  const appCount = ss.getSheetByName(CONFIG.APPLICATIONS_SHEET_NAME).getLastRow() - 1;
  return {
    success: true,
    week_hours: 0,
    eod_count: 0,
    active_tasks: 0,
    candidates: appCount
  };
}
