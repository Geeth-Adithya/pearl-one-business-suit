/**
 * Google Apps Script for Store Purchase & Sell Application
 * 
 * Instructions:
 * 1. Go to Google Sheets and create a new sheet.
 * 2. Set the first row as Headers matching your database columns:
 *    item_code, name, attribute, type, category_id, purchasing_price, margin_percent, selling_price, unit, description, supplier_name
 * 3. Click Extensions > Apps Script.
 * 4. Paste this entire code into Code.gs, replacing any existing code.
 * 5. Click Save.
 * 6. Click Deploy > New deployment.
 * 7. Select type: "Web app".
 * 8. Execute as: "Me".
 * 9. Who has access: "Anyone".
 * 10. Click Deploy and copy the "Web app URL".
 * 11. Paste that URL into the Google Sheets Sync settings in your admin panel.
 */

function doGet(e) {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  var data = sheet.getDataRange().getValues();
  
  if (data.length <= 1) {
    return ContentService.createTextOutput(JSON.stringify([])).setMimeType(ContentService.MimeType.JSON);
  }
  
  var headers = data[0];
  var rows = [];
  
  for (var i = 1; i < data.length; i++) {
    var rowObject = {};
    for (var j = 0; j < headers.length; j++) {
      rowObject[headers[j]] = data[i][j];
    }
    rows.push(rowObject);
  }
  
  return ContentService.createTextOutput(JSON.stringify(rows)).setMimeType(ContentService.MimeType.JSON);
}

// --- Helper to ensure first row headers exist ---
var DEFAULT_HEADERS = ["item_code", "name", "attribute", "type", "category_id", "purchasing_price", "margin_percent", "selling_price", "unit", "description", "supplier_name"];

function onOpen() {
  SpreadsheetApp.getUi().createMenu('Store Setup')
    .addItem('Set Default Headers', 'setDefaultHeaders')
    .addToUi();
}

function setDefaultHeaders() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getActiveSheet();

  // Determine the width to check (existing columns or default length)
  var checkCols = Math.max(sheet.getLastColumn(), DEFAULT_HEADERS.length);
  var firstRow = sheet.getRange(1, 1, 1, checkCols).getValues()[0];

  var hasData = false;
  for (var i = 0; i < firstRow.length; i++) {
    if (String(firstRow[i]).trim() !== '') { hasData = true; break; }
  }

  if (hasData) {
    ss.toast('First row already contains values — headers not changed.', 'Headers', 5);
    return;
  }

  sheet.getRange(1, 1, 1, DEFAULT_HEADERS.length).setValues([DEFAULT_HEADERS]);
  ss.toast('Default headers set on first row.', 'Headers', 5);
}

function getHeaders(sheet) {
  var data = sheet.getDataRange().getValues();
  if (data.length === 0) {
    return [];
  }
  return data[0];
}

function ensureHeaders(sheet) {
  var headers = getHeaders(sheet);
  if (!headers || headers.length === 0 || headers.every(function(value) { return String(value).trim() === ''; })) {
    sheet.getRange(1, 1, 1, DEFAULT_HEADERS.length).setValues([DEFAULT_HEADERS]);
    return DEFAULT_HEADERS;
  }
  return headers;
}

function findRowIndexByItemCode(sheet, headers, itemCode) {
  var itemCodeIndex = headers.indexOf('item_code');
  if (itemCodeIndex === -1) {
    return -1;
  }

  var lastRow = sheet.getLastRow();
  if (lastRow < 2) {
    return -1;
  }

  var values = sheet.getRange(2, 1, lastRow - 1, headers.length).getValues();
  for (var i = 0; i < values.length; i++) {
    if (String(values[i][itemCodeIndex]).trim() === String(itemCode).trim()) {
      return i + 2;
    }
  }
  return -1;
}

function doPost(e) {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  var headers = ensureHeaders(sheet);

  try {
    var postData = JSON.parse(e.postData.contents);
    var action = String(postData.action || '').toLowerCase();
    var data = postData.data || {};
    var itemCode = data.item_code || data.itemCode || data.item_code;

    if (!action) {
      throw new Error('Missing action in payload.');
    }

    function buildRowValues() {
      return headers.map(function(header) {
        return data[header] !== undefined ? data[header] : '';
      });
    }

    if (action === 'delete') {
      var deleteRow = findRowIndexByItemCode(sheet, headers, itemCode);
      if (deleteRow === -1) {
        throw new Error('Product not found for delete.');
      }
      sheet.deleteRow(deleteRow);
      return ContentService.createTextOutput(JSON.stringify({"status": "success", "message": "Product deleted from sheet."})).setMimeType(ContentService.MimeType.JSON);
    }

    if (action === 'update') {
      var updateRow = findRowIndexByItemCode(sheet, headers, itemCode);
      if (updateRow === -1) {
        throw new Error('Product not found for update.');
      }
      sheet.getRange(updateRow, 1, 1, headers.length).setValues([buildRowValues()]);
      return ContentService.createTextOutput(JSON.stringify({"status": "success", "message": "Product updated in sheet."})).setMimeType(ContentService.MimeType.JSON);
    }

    if (action === 'add') {
      var existingRow = findRowIndexByItemCode(sheet, headers, itemCode);
      if (existingRow !== -1) {
        sheet.getRange(existingRow, 1, 1, headers.length).setValues([buildRowValues()]);
        return ContentService.createTextOutput(JSON.stringify({"status": "success", "message": "Product already existed and was updated in sheet."})).setMimeType(ContentService.MimeType.JSON);
      }
      sheet.appendRow(buildRowValues());
      return ContentService.createTextOutput(JSON.stringify({"status": "success", "message": "Product added to sheet."})).setMimeType(ContentService.MimeType.JSON);
    }

    throw new Error('Unsupported action: ' + action);
  } catch(error) {
    return ContentService.createTextOutput(JSON.stringify({"status": "error", "message": String(error)})).setMimeType(ContentService.MimeType.JSON);
  }
}
