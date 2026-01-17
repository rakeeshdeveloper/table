<!DOCTYPE html>
<html>
<head>
    <title>Table with 0 Row & 0 Column Drag + Merge/Split</title>
    <style>
        body { font-family: Arial; }

        table {
            border-collapse: collapse;
            width: 100%;
            min-width: 600px;
        }

        td, th {
            border: 1px solid #333;
            padding: 6px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            cursor: move;
            user-select: none;
        }

        .corner-cell {
            background-color: #f2f2f2;
            cursor: default;
        }

        .dragging {
            opacity: 0.4;
        }

        input {
            width: 100%;
        }

        .btn {
            font-size: 14px;
            padding: 2px 5px;
            margin: 2px;
            cursor: pointer;
        }

        .disabled {
            opacity: 0.3;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .split-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 3px 6px;
            margin-top: 5px;
            cursor: pointer;
            border-radius: 3px;
            font-size: 14px;
        }
        
        .delete-btn {
            background-color: #ff4444;
            color: white;
            border: none;
            padding: 3px 6px;
            margin-top: 5px;
            cursor: pointer;
            border-radius: 3px;
            font-size: 14px;
        }
        
        .control-panel {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        
        .control-btn {
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 8px 15px;
            margin: 5px;
            cursor: pointer;
            border-radius: 3px;
            font-size: 14px;
        }
        
        .control-btn.delete {
            background-color: #ff4444;
        }
        
        .selection-highlight {
            background-color: #e3f2fd !important;
        }
        
        .cell-selected {
            background-color: #ffeb3b !important;
        }
    </style>
</head>
<body>

<h3>Table with 0 Row & 0 Column Drag + Merge/Split</h3>

<div class="control-panel">
    <button class="control-btn" onclick="addRow()">➕ Add Data Row</button>
    <button class="control-btn" onclick="addColumn()">➕ Add Column</button>
    <button class="control-btn delete" onclick="deleteSelectedRow()">🗑️ Delete Selected Row</button>
    <button class="control-btn delete" onclick="deleteSelectedColumn()">🗑️ Delete Selected Column</button>
</div>

<br>

<table id="tbl">
    <tbody>
        <!-- Table will be initialized by JavaScript -->
    </tbody>
</table>

<script>
let cols = 4;
let selectedRow = null;
let selectedCol = null;
let selectedCell = null;
let dragSrc = null;

/* INITIALIZE TABLE WITH PROPER STRUCTURE */
function initializeTable() {
    const tbody = document.querySelector("#tbl tbody");
    tbody.innerHTML = '';
    
    // Create 0th row (column headers)
    const headerRow = document.createElement("tr");
    
    // Corner cell (top-left, empty)
    const cornerCell = document.createElement("th");
    cornerCell.className = "corner-cell";
    cornerCell.innerHTML = "&nbsp;";
    headerRow.appendChild(cornerCell);
    
    // Create column headers (A, B, C, D)
    for (let i = 0; i < cols; i++) {
        const th = document.createElement("th");
        th.textContent = String.fromCharCode(65 + i); // A, B, C, D
        th.draggable = true;
        headerRow.appendChild(th);
    }
    
    tbody.appendChild(headerRow);
    
    // Create 2 initial data rows
    for (let rowNum = 1; rowNum <= 2; rowNum++) {
        const dataRow = document.createElement("tr");
        
        // Row header
        const rowHeader = document.createElement("th");
        rowHeader.textContent = `Row ${rowNum}`;
        rowHeader.draggable = true;
        dataRow.appendChild(rowHeader);
        
        // Data cells
        for (let col = 0; col < cols; col++) {
            const td = document.createElement("td");
            td.innerHTML = `
                <input type="text" placeholder="Data ${rowNum}${String.fromCharCode(65 + col)}"><br>
                <button class="btn" onclick="mergeLeft(this)">⬅</button>
                <button class="btn" onclick="mergeRight(this)">➡</button>
                <button class="btn" onclick="mergeUp(this)">⬆</button>
                <button class="btn" onclick="mergeDown(this)">⬇</button><br>
                <button class="split-btn" onclick="splitCell(this)">Split (1×1)</button>
                <button class="delete-btn" onclick="deleteThisCell(this, event)">Delete Cell</button>
            `;
            td.addEventListener('click', (e) => {
                if (e.target.tagName !== 'BUTTON') {
                    selectCell(dataRow.rowIndex, td.cellIndex);
                }
            });
            dataRow.appendChild(td);
        }
        
        tbody.appendChild(dataRow);
    }
    
    // Setup drag and drop
    setupDragAndDrop();
    
    // Add click handlers for row/column selection
    addSelectionHandlers();
}

/* SETUP DRAG AND DROP */
function setupDragAndDrop() {
    // Clear existing listeners
    document.removeEventListener("dragstart", handleDragStart);
    document.removeEventListener("dragend", handleDragEnd);
    document.removeEventListener("dragover", handleDragOver);
    document.removeEventListener("drop", handleDrop);
    
    // Add new listeners
    document.addEventListener("dragstart", handleDragStart);
    document.addEventListener("dragend", handleDragEnd);
    document.addEventListener("dragover", handleDragOver);
    document.addEventListener("drop", handleDrop);
}

/* ADD SELECTION HANDLERS */
function addSelectionHandlers() {
    const tbody = document.querySelector("#tbl tbody");
    
    // Row header click (for row selection)
    for (let i = 1; i < tbody.rows.length; i++) {
        const rowHeader = tbody.rows[i].cells[0];
        rowHeader.addEventListener('click', (e) => {
            if (!e.target.draggable) return;
            selectRow(i);
        });
    }
    
    // Column header click (for column selection)
    const headerRow = tbody.rows[0];
    for (let i = 1; i < headerRow.cells.length; i++) {
        const colHeader = headerRow.cells[i];
        colHeader.addEventListener('click', (e) => {
            if (!e.target.draggable) return;
            selectColumn(i);
        });
    }
}

/* DRAG START */
function handleDragStart(e) {
    if (e.target.tagName === "TH" && !e.target.classList.contains("corner-cell")) {
        dragSrc = e.target;
        e.target.classList.add("dragging");
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', 'drag');
    }
}

/* DRAG END */
function handleDragEnd(e) {
    if (e.target.tagName === "TH") {
        e.target.classList.remove("dragging");
    }
    dragSrc = null;
}

/* DRAG OVER */
function handleDragOver(e) {
    if (e.target.tagName === "TH" && !e.target.classList.contains("corner-cell")) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }
}

/* DROP */
function handleDrop(e) {
    if (e.target.tagName !== "TH") return;
    if (!dragSrc || dragSrc === e.target) return;
    if (e.target.classList.contains("corner-cell")) return;

    e.preventDefault();
    
    const table = document.getElementById("tbl");
    const srcRowIndex = dragSrc.parentElement.rowIndex;
    const srcCellIndex = dragSrc.cellIndex;
    const targetRowIndex = e.target.parentElement.rowIndex;
    const targetCellIndex = e.target.cellIndex;

    // Column drag (0th row)
    if (srcRowIndex === 0 && targetRowIndex === 0) {
        // Reorder columns
        for (let i = 0; i < table.rows.length; i++) {
            const row = table.rows[i];
            const srcCell = row.cells[srcCellIndex];
            const targetCell = row.cells[targetCellIndex];
            
            if (srcCell && targetCell) {
                if (srcCellIndex < targetCellIndex) {
                    row.insertBefore(srcCell, targetCell.nextSibling);
                } else {
                    row.insertBefore(srcCell, targetCell);
                }
            }
        }
        updateColumnHeaders();
    }
    // Row drag (0th column)
    else if (srcCellIndex === 0 && targetCellIndex === 0) {
        // Reorder rows
        const srcRow = dragSrc.parentElement;
        const targetRow = e.target.parentElement;
        
        if (srcRowIndex < targetRowIndex) {
            table.tBodies[0].insertBefore(srcRow, targetRow.nextSibling);
        } else {
            table.tBodies[0].insertBefore(srcRow, targetRow);
        }
        updateRowNumbers();
    }

    updateAllButtons();
    addSelectionHandlers(); // Re-add handlers after DOM changes
}

/* UPDATE ROW NUMBERS */
function updateRowNumbers() {
    const table = document.getElementById("tbl");
    
    // Start from row 1 (skip header row)
    for (let i = 1; i < table.rows.length; i++) {
        const rowHeader = table.rows[i].cells[0];
        if (rowHeader && rowHeader.tagName === "TH") {
            rowHeader.textContent = `Row ${i}`;
        }
    }
}

/* UPDATE COLUMN HEADERS */
function updateColumnHeaders() {
    const table = document.getElementById("tbl");
    const headerRow = table.rows[0];
    
    // Start from column 1 (skip corner cell)
    for (let i = 1; i < headerRow.cells.length; i++) {
        const th = headerRow.cells[i];
        if (th && th.tagName === "TH") {
            th.textContent = String.fromCharCode(64 + i); // A, B, C, etc.
        }
    }
}

/* UPDATE ALL BUTTONS */
function updateAllButtons() {
    const table = document.getElementById("tbl");
    
    for (let i = 1; i < table.rows.length; i++) {
        const cells = table.rows[i].cells;
        for (let j = 1; j < cells.length; j++) {
            const td = cells[j];
            if (td.tagName !== "TD") continue;
            
            // Update split button text
            const splitBtn = td.querySelector('.split-btn');
            if (splitBtn) {
                const colspan = td.colSpan || 1;
                const rowspan = td.rowSpan || 1;
                
                if (colspan > 1 || rowspan > 1) {
                    splitBtn.textContent = `Split (${colspan}×${rowspan})`;
                } else {
                    splitBtn.textContent = 'Split (1×1)';
                }
            }
        }
    }
}

/* ADD ROW */
function addRow() {
    const table = document.getElementById("tbl");
    const newRowNum = table.rows.length; // Because we have header row at index 0
    
    const dataRow = document.createElement("tr");
    
    // Row header
    const rowHeader = document.createElement("th");
    rowHeader.textContent = `Row ${newRowNum}`;
    rowHeader.draggable = true;
    rowHeader.addEventListener('click', (e) => {
        if (!e.target.draggable) return;
        selectRow(newRowNum);
    });
    dataRow.appendChild(rowHeader);
    
    // Data cells - match current number of columns
    const colCount = table.rows[0].cells.length - 1; // Exclude corner cell
    
    for (let col = 0; col < colCount; col++) {
        const td = document.createElement("td");
        td.innerHTML = `
            <input type="text" placeholder="Data ${newRowNum}${String.fromCharCode(65 + col)}"><br>
            <button class="btn" onclick="mergeLeft(this)">⬅</button>
            <button class="btn" onclick="mergeRight(this)">➡</button>
            <button class="btn" onclick="mergeUp(this)">⬆</button>
            <button class="btn" onclick="mergeDown(this)">⬇</button><br>
            <button class="split-btn" onclick="splitCell(this)">Split (1×1)</button>
            <button class="delete-btn" onclick="deleteThisCell(this, event)">Delete Cell</button>
        `;
        td.addEventListener('click', (e) => {
            if (e.target.tagName !== 'BUTTON') {
                selectCell(newRowNum, col + 1); // +1 for row header
            }
        });
        dataRow.appendChild(td);
    }
    
    table.tBodies[0].appendChild(dataRow);
    updateAllButtons();
}

/* ADD COLUMN */
function addColumn() {
    const table = document.getElementById("tbl");
    const headerRow = table.rows[0];
    const newColIndex = headerRow.cells.length;
    const colLetter = String.fromCharCode(64 + newColIndex); // Next letter
    
    // Add to header row
    const newHeader = document.createElement("th");
    newHeader.textContent = colLetter;
    newHeader.draggable = true;
    newHeader.addEventListener('click', (e) => {
        if (!e.target.draggable) return;
        selectColumn(newColIndex);
    });
    headerRow.appendChild(newHeader);
    
    // Add to all data rows
    for (let i = 1; i < table.rows.length; i++) {
        const row = table.rows[i];
        const td = document.createElement("td");
        const rowNum = i;
        td.innerHTML = `
            <input type="text" placeholder="Data ${rowNum}${colLetter}"><br>
            <button class="btn" onclick="mergeLeft(this)">⬅</button>
            <button class="btn" onclick="mergeRight(this)">➡</button>
            <button class="btn" onclick="mergeUp(this)">⬆</button>
            <button class="btn" onclick="mergeDown(this)">⬇</button><br>
            <button class="split-btn" onclick="splitCell(this)">Split (1×1)</button>
            <button class="delete-btn" onclick="deleteThisCell(this, event)">Delete Cell</button>
        `;
        td.addEventListener('click', (e) => {
            if (e.target.tagName !== 'BUTTON') {
                selectCell(rowNum, newColIndex);
            }
        });
        row.appendChild(td);
    }
    
    setupDragAndDrop();
    updateAllButtons();
}

/* SELECTION FUNCTIONS */
function selectRow(rowIndex) {
    selectedRow = rowIndex;
    selectedCol = null;
    selectedCell = null;
    updateSelectionUI();
}

function selectColumn(colIndex) {
    selectedCol = colIndex;
    selectedRow = null;
    selectedCell = null;
    updateSelectionUI();
}

function selectCell(rowIndex, colIndex) {
    selectedCell = { row: rowIndex, col: colIndex };
    selectedRow = null;
    selectedCol = null;
    updateSelectionUI();
}

function updateSelectionUI() {
    const table = document.getElementById("tbl");
    
    // Clear all selections
    for (let i = 0; i < table.rows.length; i++) {
        const cells = table.rows[i].cells;
        for (let j = 0; j < cells.length; j++) {
            cells[j].classList.remove('selection-highlight', 'cell-selected');
        }
    }
    
    // Apply new selections
    if (selectedRow !== null && selectedRow < table.rows.length) {
        const row = table.rows[selectedRow];
        for (let j = 0; j < row.cells.length; j++) {
            row.cells[j].classList.add('selection-highlight');
        }
    }
    
    if (selectedCol !== null) {
        for (let i = 0; i < table.rows.length; i++) {
            if (selectedCol < table.rows[i].cells.length) {
                table.rows[i].cells[selectedCol].classList.add('selection-highlight');
            }
        }
    }
    
    if (selectedCell !== null && 
        selectedCell.row < table.rows.length && 
        selectedCell.col < table.rows[selectedCell.row].cells.length) {
        const cell = table.rows[selectedCell.row].cells[selectedCell.col];
        if (cell) {
            cell.classList.add('cell-selected');
        }
    }
}

/* DELETE FUNCTIONS */
function deleteSelectedRow() {
    if (selectedRow === null || selectedRow === 0) {
        alert("Please select a data row first by clicking on the row number");
        return;
    }
    
    const table = document.getElementById("tbl");
    if (table.rows.length <= 2) { // 1 header + 1 data row
        alert("Cannot delete the last data row");
        return;
    }
    
    table.deleteRow(selectedRow);
    selectedRow = null;
    updateRowNumbers();
    updateAllButtons();
    addSelectionHandlers();
}

function deleteSelectedColumn() {
    if (selectedCol === null || selectedCol === 0) {
        alert("Please select a data column first by clicking on the column letter");
        return;
    }
    
    const table = document.getElementById("tbl");
    if (table.rows[0].cells.length <= 2) { // 1 corner + 1 data column
        alert("Cannot delete the last column");
        return;
    }
    
    // Delete column from all rows
    for (let i = 0; i < table.rows.length; i++) {
        const row = table.rows[i];
        const cellToDelete = row.cells[selectedCol];
        if (cellToDelete) {
            if (cellToDelete.colSpan > 1) {
                cellToDelete.colSpan--;
            } else {
                row.deleteCell(selectedCol);
            }
        }
    }
    
    selectedCol = null;
    updateColumnHeaders();
    updateAllButtons();
    addSelectionHandlers();
}

function deleteThisCell(btn, event) {
    //event.stopPropagation();

    const td = btn.closest("td");
    const tr = td.parentElement;

    if (td.colSpan > 1 || td.rowSpan > 1) {
        alert("Merged cell cannot be deleted safely");
        return;
    }

    td.remove();
}


/* MERGE FUNCTIONS */
function mergeLeft(btn) {
    const td = btn.closest("td");
    const leftTd = td.previousElementSibling;
    
    if (!leftTd || leftTd.tagName === "TH") {
        alert("Left cell not available or is a header");
        return;
    }
    
    leftTd.colSpan = (leftTd.colSpan || 1) + (td.colSpan || 1);
    td.remove();
    updateAllButtons();
}

function mergeRight(btn) {
    const td = btn.closest("td");
    const rightTd = td.nextElementSibling;
    
    if (!rightTd || rightTd.tagName === "TH") {
        alert("Right cell not available or is a header");
        return;
    }
    
    td.colSpan = (td.colSpan || 1) + (rightTd.colSpan || 1);
    rightTd.remove();
    updateAllButtons();
}

function mergeUp(btn) {
    const td = btn.closest("td");
    const tr = td.parentElement;
    const table = document.getElementById("tbl");
    const rowIndex = tr.rowIndex;
    const prevRow = table.rows[rowIndex - 1];
    
    if (!prevRow) {
        alert("Above row not available");
        return;
    }
    
    const cellIndex = td.cellIndex;
    const aboveTd = prevRow.cells[cellIndex];
    
    if (!aboveTd || aboveTd.tagName === "TH") {
        alert("Above cell not available or is a header");
        return;
    }
    
    aboveTd.rowSpan = (aboveTd.rowSpan || 1) + (td.rowSpan || 1);
    td.remove();
    updateAllButtons();
}

function mergeDown(btn) {
    const td = btn.closest("td");
    const tr = td.parentElement;
    const table = document.getElementById("tbl");
    const rowIndex = tr.rowIndex;
    const nextRow = table.rows[rowIndex + 1];
    
    if (!nextRow) {
        alert("Below row not available");
        return;
    }
    
    const cellIndex = td.cellIndex;
    const belowTd = nextRow.cells[cellIndex];
    
    if (!belowTd || belowTd.tagName === "TH") {
        alert("Below cell not available or is a header");
        return;
    }
    
    td.rowSpan = (td.rowSpan || 1) + (belowTd.rowSpan || 1);
    belowTd.remove();
    updateAllButtons();
}

/* SPLIT CELL */
function splitCell(btn) {
    const td = btn.closest("td");
    const colspan = td.colSpan || 1;
    const rowspan = td.rowSpan || 1;
    
    if (colspan === 1 && rowspan === 1) {
        alert("Cell is not merged (1×1)");
        return;
    }
    
    let splitDirection = "col";
    
    if (colspan > 1 && rowspan > 1) {
        splitDirection = prompt("Split which direction?\nEnter 'col' for column split or 'row' for row split:", "col");
        if (!splitDirection) return;
        splitDirection = splitDirection.toLowerCase();
    } else if (colspan > 1) {
        splitDirection = "col";
    } else if (rowspan > 1) {
        splitDirection = "row";
    }
    
    if (splitDirection === "col") {
        splitColumnInternal(td, colspan);
    } else if (splitDirection === "row") {
        splitRowInternal(td, rowspan);
    }
    
    updateAllButtons();
}

function splitColumnInternal(td, colspan) {
    if (colspan <= 1) return;
    
    const tr = td.parentElement;
    const cellIndex = td.cellIndex;
    
    // Reduce colspan by 1
    td.colSpan = colspan - 1;
    
    // Create new cell to the right
    const newTd = document.createElement("td");
    newTd.addEventListener('click', (e) => {
        if (e.target.tagName !== 'BUTTON') {
            selectCell(tr.rowIndex, cellIndex + 1);
        }
    });
    
    newTd.innerHTML = `
        <input type="text" placeholder="Data"><br>
        <button class="btn" onclick="mergeLeft(this)">⬅</button>
        <button class="btn" onclick="mergeRight(this)">➡</button>
        <button class="btn" onclick="mergeUp(this)">⬆</button>
        <button class="btn" onclick="mergeDown(this)">⬇</button><br>
        <button class="split-btn" onclick="splitCell(this)">Split (1×${td.rowSpan || 1})</button>
        <button class="delete-btn" onclick="deleteThisCell(this, event)">Delete Cell</button>
    `;
    
    // Insert after current td
    tr.insertBefore(newTd, td.nextSibling);
}

function splitRowInternal(td, rowspan) {
    if (rowspan <= 1) return;
    
    const tr = td.parentElement;
    const table = document.getElementById("tbl");
    const rowIndex = tr.rowIndex;
    const cellIndex = td.cellIndex;
    
    // Reduce rowspan by 1
    td.rowSpan = rowspan - 1;
    
    // Create new cell below
    const newRow = table.rows[rowIndex + (rowspan - 1)];
    
    if (newRow) {
        const newTd = document.createElement("td");
        newTd.addEventListener('click', (e) => {
            if (e.target.tagName !== 'BUTTON') {
                selectCell(newRow.rowIndex, cellIndex);
            }
        });
        
        newTd.innerHTML = `
            <input type="text" placeholder="Data"><br>
            <button class="btn" onclick="mergeLeft(this)">⬅</button>
            <button class="btn" onclick="mergeRight(this)">➡</button>
            <button class="btn" onclick="mergeUp(this)">⬆</button>
            <button class="btn" onclick="mergeDown(this)">⬇</button><br>
            <button class="split-btn" onclick="splitCell(this)">Split (${td.colSpan || 1}×1)</button>
            <button class="delete-btn" onclick="deleteThisCell(this, event)">Delete Cell</button>
        `;
        
        // Insert at correct position
        const beforeTd = newRow.cells[cellIndex];
        if (beforeTd) {
            newRow.insertBefore(newTd, beforeTd);
        } else {
            newRow.appendChild(newTd);
        }
    }
}

/* INITIALIZE */
initializeTable();
</script>

</body>
</html>
