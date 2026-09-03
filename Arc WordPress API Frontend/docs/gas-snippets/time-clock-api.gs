/**
 * doPost endpoint for IPC Time Clock.
 * Add this file to the Apps Script project and deploy the Web App.
 * Save the API key in ScriptProperties: API_KEY.
 *
 * Supported actions:
 * - clock_in:  { client, activity }
 * - clock_out: {}
 * - get_state: {}
 * - get_stats: {}
 */

function doPost(e) {
  const body = JSON.parse(e.postData.contents || '{}');

  if (body.api_key !== PropertiesService.getScriptProperties().getProperty('API_KEY')) {
    return jsonResponse_({ success: false, error: 'Invalid API key' });
  }

  const action = body.action;

  switch (action) {
    case 'clock_in':
      return jsonResponse_(clockInFromWP_(body));
    case 'clock_out':
      return jsonResponse_(clockOutFromWP_(body));
    case 'get_state':
      return jsonResponse_(getStateFromWP_(body));
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

function clockInFromWP_(data) {
  const ss = ss_();
  const sh = ss.getSheetByName(TABS.LOG);
  const now = new Date();
  const row = [
    Utilities.getUuid(),              // Entry ID
    data.wp_email || '',              // Employee
    now,                              // Work Date
    data.client || '',                // Client / Company
    data.activity || '',              // Activity
    now,                              // Start
    '',                               // End
    0,                                // Unpaid Break (min)
    0,                                // Paid Hours
    'Yes',                            // Billable
    '',                               // Notes
    'WordPress',                      // Source
    'OPEN',                           // Status
    '', '', '', '', '', '', '', '', ''// Flags, Approved By, Approved At, Week/Month Key, Project, Task, Tags
  ];
  sh.appendRow(row);
  return { success: true, message: 'Entry recorded', timestamp: now };
}

function clockOutFromWP_(data) {
  const ss = ss_();
  const sh = ss.getSheetByName(TABS.LOG);
  const values = sh.getDataRange().getValues();
  const email = String(data.wp_email || '').toLowerCase().trim();

  for (let i = values.length - 1; i >= 1; i--) {
    if (
      String(values[i][1]).toLowerCase().trim() === email &&
      String(values[i][12]) === 'OPEN'
    ) {
      const start = values[i][5] ? new Date(values[i][5]) : null;
      const end = new Date();
      let hours = 0;
      if (start && !isNaN(start.getTime())) {
        hours = (end - start) / 3600000;
      }
      const row = i + 1;
      sh.getRange(row, 7).setValue(end);      // End (column 7 is 1-based index 6)
      sh.getRange(row, 9).setValue(hours);    // Paid Hours
      sh.getRange(row, 13).setValue('PENDING'); // Status
      return { success: true, message: 'Exit recorded', hours: Number(hours.toFixed(2)) };
    }
  }
  return { success: false, error: 'There is no open entry for this user' };
}

function getStateFromWP_(data) {
  const ss = ss_();
  const sh = ss.getSheetByName(TABS.LOG);
  const values = sh.getDataRange().getValues();
  const email = String(data.wp_email || '').toLowerCase().trim();

  for (let i = values.length - 1; i >= 1; i--) {
    if (
      String(values[i][1]).toLowerCase().trim() === email &&
      String(values[i][12]) === 'OPEN'
    ) {
      return { success: true, state: 'clocked_in', start: values[i][5] };
    }
  }
  return { success: true, state: 'clocked_out' };
}

function getStatsFromWP_(data) {
  const ss = ss_();
  const sh = ss.getSheetByName(TABS.LOG);
  const values = sh.getDataRange().getValues().slice(1);
  const email = String(data.wp_email || '').toLowerCase().trim();

  const weekHours = values.reduce(function(sum, row) {
    if (String(row[1]).toLowerCase().trim() === email && row[8]) {
      return sum + Number(row[8]);
    }
    return sum;
  }, 0);

  return {
    success: true,
    week_hours: Number(weekHours.toFixed(2)),
    eod_count: 0,
    active_tasks: 0,
    candidates: 0
  };
}
