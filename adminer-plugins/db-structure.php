<?php

/**
 * Adds the DB structure to the SQL command page
 * @link https://www.adminer.org/plugins/#use
 * @author Emanuele "ToX" Toscano, https://github.com/tox82
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
 */
class DbStructure
{
    /**
     * Keywords that indicate sensitive data
     * @var array
     */
    private $sensitiveKeywords = ['password', 'token', 'secret'];

    /**
     * Masks sensitive data with placeholders
     * @param string $value The value to check
     * @param string $fieldName The field name
     * @return string The masked value or original value
     */
    private function maskSensitiveData($value, $fieldName)
    {
        if (empty($value)) {
            return $value;
        }

        $fieldLower = strtolower($fieldName);

        // Check if field name contains sensitive keywords
        foreach ($this->sensitiveKeywords as $keyword) {
            if (strpos($fieldLower, $keyword) !== false) {
                return '[HIDDEN_' . strtoupper($keyword) . ']';
            }
        }

        return $value;
    }

    public function head($Hb = null)
    {
        // Handle AJAX requests first
        if (isset($_POST['db_structure_action']) && $_POST['db_structure_action'] === 'get_data') {
            $this->handleDataRequest();
            exit;
        }

        if (strpos($_SERVER['QUERY_STRING'], '&sql=') !== false) {
            $tables = array_keys(Adminer\tables_list());
            $tableData = [];

            foreach ($tables as $table) {
                $engine = Adminer\table_status($table)['Engine'] ?? 'Unknown';

                // Table structure
                $structureContent = "## TABLE: " . $table . " ($engine)\n";
                foreach (Adminer\fields($table) as $field) {
                    $type = $field["type"];
                    $name = $field["field"];
                    $null = $field["null"] ? "NULL" : "NOT NULL";
                    $auto = $field["auto_increment"] ? "AUTO_INCREMENT" : "";
                    $structureContent .= "- $name - $type $null $auto\n";
                }

                // Indexes
                $indexesContent = "";
                $indexes = Adminer\indexes($table);
                if (!empty($indexes)) {
                    $indexesContent .= "\n### INDEXES:\n";
                    foreach ($indexes as $index) {
                        $type = $index["type"];
                        $columns = implode(", ", $index["columns"]);
                        $indexType = $type === "PRIMARY" ? "PRIMARY KEY" :
                                   ($type === "UNIQUE" ? "UNIQUE INDEX" : "INDEX");
                        $indexesContent .= "- $indexType ($columns)\n";
                    }
                }

                // Foreign Keys
                $foreignKeysContent = "";
                $foreignKeys = Adminer\foreign_keys($table);
                if (!empty($foreignKeys)) {
                    $foreignKeysContent .= "\n### FOREIGN KEYS:\n";
                    foreach ($foreignKeys as $fk) {
                        $source = implode(", ", $fk["source"]);
                        $target = implode(", ", $fk["target"]);
                        $constraints = isset($fk["on_delete"]) ? " ON DELETE " . $fk["on_delete"] : "";
                        $constraints .= isset($fk["on_update"]) ? " ON UPDATE " . $fk["on_update"] : "";
                        $foreignKeysContent .= "- $source -> " . $fk["table"] . "($target)$constraints\n";
                    }
                }

                // Table data (CSV sample)
                $dataContent = $this->getTableDataAsCSV($table, 5); // Default 5 records

                $tableData[] = [
                    'name' => $table,
                    'structure' => $structureContent,
                    'indexes' => $indexesContent,
                    'foreignKeys' => $foreignKeysContent,
                    'data' => $dataContent
                ];
            }

            echo '<script nonce="' . Adminer\get_nonce() . '">
                document.addEventListener("DOMContentLoaded", function() {
                    const tables = ' . json_encode($tableData) . ';
                    const container = document.createElement("div");
                    container.style.display = "flex";
                    container.style.flexDirection = "column";
                    container.style.marginTop = "70px";

                    const title = document.createElement("h2");
                    title.textContent = "DB Structure";
                    container.appendChild(title);

                    // Main options (radio buttons)
                    const modeContainer = document.createElement("div");
                    modeContainer.style.marginBottom = "16px";
                    modeContainer.innerHTML = \'<div class="adminer-button" style="margin-bottom: 8px;">Mode:</div>\';

                    const modeOptions = document.createElement("div");
                    modeOptions.style.display = "flex";
                    modeOptions.style.gap = "16px";

                    const structureRadio = document.createElement("input");
                    structureRadio.type = "radio";
                    structureRadio.name = "mode";
                    structureRadio.value = "structure";
                    structureRadio.id = "mode-structure";
                    structureRadio.checked = true;

                    const structureLabel = document.createElement("label");
                    structureLabel.htmlFor = "mode-structure";
                    structureLabel.style.display = "inline-flex";
                    structureLabel.style.alignItems = "center";
                    structureLabel.style.gap = "4px";
                    structureLabel.style.fontWeight = "normal";
                    structureLabel.appendChild(structureRadio);
                    structureLabel.appendChild(document.createTextNode("Structure"));

                    const dataRadio = document.createElement("input");
                    dataRadio.type = "radio";
                    dataRadio.name = "mode";
                    dataRadio.value = "data";
                    dataRadio.id = "mode-data";

                    const dataLabel = document.createElement("label");
                    dataLabel.htmlFor = "mode-data";
                    dataLabel.style.display = "inline-flex";
                    dataLabel.style.alignItems = "center";
                    dataLabel.style.gap = "4px";
                    dataLabel.style.fontWeight = "normal";
                    dataLabel.appendChild(dataRadio);
                    dataLabel.appendChild(document.createTextNode("Data"));

                    modeOptions.appendChild(structureLabel);
                    modeOptions.appendChild(dataLabel);
                    modeContainer.appendChild(modeOptions);
                    container.appendChild(modeContainer);

                    // Structure settings (checkboxes)
                    const structureSettings = {};
                    const structureSettingsContainer = document.createElement("div");
                    structureSettingsContainer.style.display = "flex";
                    structureSettingsContainer.style.gap = "16px";
                    structureSettingsContainer.style.marginBottom = "12px";
                    structureSettingsContainer.innerHTML = \'<div class="adminer-button">Extract:</div>\';

                    const structureOptions = [
                        { label: "Fields", key: "fields", checked: true },
                        { label: "Indexes", key: "indexes", checked: true },
                        { label: "Foreign Keys", key: "keys", checked: true }
                    ];

                    structureOptions.forEach(opt => {
                        const label = document.createElement("label");
                        label.style.display = "inline-flex";
                        label.style.alignItems = "center";
                        label.style.gap = "4px";
                        label.style.fontWeight = "normal";
                        label.style.fontSize = "14px";
                        const checkbox = document.createElement("input");
                        checkbox.type = "checkbox";
                        checkbox.checked = opt.checked;
                        checkbox.dataset.optionKey = opt.key;
                        checkbox.addEventListener("change", updateTextarea);
                        structureSettings[opt.key] = checkbox;
                        label.appendChild(checkbox);
                        label.appendChild(document.createTextNode(opt.label));
                        structureSettingsContainer.appendChild(label);
                    });
                    container.appendChild(structureSettingsContainer);

                    // Data settings (number of records)
                    const dataSettingsContainer = document.createElement("div");
                    dataSettingsContainer.style.display = "none";
                    dataSettingsContainer.style.alignItems = "center";
                    dataSettingsContainer.style.gap = "8px";
                    dataSettingsContainer.style.marginBottom = "12px";

                    const recordsLabel = document.createElement("label");
                    recordsLabel.style.fontWeight = "normal";
                    recordsLabel.style.fontSize = "14px";
                    recordsLabel.innerHTML = \'<span class="adminer-button">Records per table:</span>\';

                    const recordsInput = document.createElement("input");
                    recordsInput.type = "number";
                    recordsInput.value = "5";
                    recordsInput.min = "1";
                    recordsInput.max = "1000";
                    recordsInput.style.width = "60px";
                    recordsInput.style.marginLeft = "8px";
                    recordsInput.addEventListener("input", function() {
                        if (dataRadio.checked) {
                            updateDataForAllTables();
                        }
                    });

                    recordsLabel.appendChild(recordsInput);
                    dataSettingsContainer.appendChild(recordsLabel);
                    container.appendChild(dataSettingsContainer);

                    // Event listeners for radio buttons
                    structureRadio.addEventListener("change", function() {
                        if (this.checked) {
                            structureSettingsContainer.style.display = "flex";
                            dataSettingsContainer.style.display = "none";
                            updateTextarea();
                        }
                    });

                    dataRadio.addEventListener("change", function() {
                        if (this.checked) {
                            structureSettingsContainer.style.display = "none";
                            dataSettingsContainer.style.display = "flex";
                            updateDataForAllTables();
                        }
                    });

                    // Toggle buttons
                    const buttonContainer = document.createElement("div");
                    buttonContainer.style.display = "flex";
                    buttonContainer.style.flexWrap = "wrap";
                    buttonContainer.style.gap = "8px";
                    buttonContainer.style.marginBottom = "16px";

                    // Toggle all tables button
                    const toggleAll = document.createElement("button");
                    toggleAll.textContent = "Show/hide all";
                    toggleAll.className = "adminer-button";
                    toggleAll.addEventListener("click", () => {
                        const allActive = Array.from(buttonContainer.querySelectorAll("button.table-toggle"))
                            .every(btn => btn.classList.contains("active"));

                        buttonContainer.querySelectorAll("button.table-toggle").forEach(btn => {
                            btn.classList.toggle("active", !allActive);
                        });

                        if (dataRadio.checked) {
                            updateDataForAllTables();
                        } else {
                            updateTextarea();
                        }
                    });
                    buttonContainer.appendChild(toggleAll);

                    // Table buttons
                    tables.forEach((table, index) => {
                        const btn = document.createElement("button");
                        btn.textContent = table.name;
                        btn.className = "adminer-button table-toggle active";
                        btn.dataset.tableIndex = index;
                        btn.addEventListener("click", function(e) {
                            this.classList.toggle("active");
                            if (dataRadio.checked) {
                                updateDataForAllTables();
                            } else {
                                updateTextarea();
                            }
                        });
                        buttonContainer.appendChild(btn);
                    });

                    // Textarea for output
                    const textarea = document.createElement("textarea");
                    textarea.id = "description";
                    textarea.style.width = "100%";
                    textarea.style.height = "400px";
                    textarea.style.marginBottom = "20px";

                    // Function to update data for all tables (AJAX)
                    function updateDataForAllTables() {
                        const activeButtons = Array.from(
                            buttonContainer.querySelectorAll("button.table-toggle.active")
                        );
                        const recordCount = parseInt(recordsInput.value) || 5;

                        if (activeButtons.length === 0) {
                            textarea.value = "";
                            return;
                        }

                        // Show loading message
                        textarea.value = "Loading data...";

                        const tableNames = activeButtons.map(btn => tables[btn.dataset.tableIndex].name);

                        // AJAX call to get data
                        fetch(window.location.href, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/x-www-form-urlencoded",
                            },
                            body: "db_structure_action=get_data&tables=" +
                                  encodeURIComponent(JSON.stringify(tableNames)) + "&limit=" + recordCount
                        })
                        .then(response => response.text())
                        .then(data => {
                            try {
                                const result = JSON.parse(data);
                                if (result.success) {
                                    textarea.value = result.data;
                                } else {
                                    textarea.value = "Error: " + (result.error || "Unknown error");
                                }
                            } catch (e) {
                                textarea.value = "Error parsing response: " + e.message;
                            }
                        })
                        .catch(error => {
                            textarea.value = "Network error: " + error.message;
                        });
                    }

                    // Update function for structure
                    function updateTextarea() {
                        const activeButtons = Array.from(
                            buttonContainer.querySelectorAll("button.table-toggle.active")
                        );
                        const output = activeButtons.map(btn => {
                            const table = tables[btn.dataset.tableIndex];
                            let content = table.structure;

                            if (structureSettings.indexes.checked && table.indexes) {
                                content += table.indexes;
                            }
                            if (structureSettings.keys.checked && table.foreignKeys) {
                                content += table.foreignKeys;
                            }

                            return content + "\n";
                        }).join("\n");
                        textarea.value = output;
                    }

                    // Build container
                    container.appendChild(buttonContainer);
                    container.appendChild(textarea);
                    document.getElementById("content").appendChild(container);

                    // Initial update
                    updateTextarea();
                });
            </script>';
        }
    }

    /**
     * Handles AJAX requests to get table data
     */
    private function handleDataRequest()
    {
        // Ensure there is no previous output
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');

        try {
            if (!isset($_POST['tables']) || !isset($_POST['limit'])) {
                echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
                return;
            }

            $tables = json_decode($_POST['tables'], true);
            $limit = (int)($_POST['limit'] ?? 5);

            if (!is_array($tables) || empty($tables)) {
                echo json_encode(['success' => false, 'error' => 'No tables specified']);
                return;
            }

            $output = '';
            foreach ($tables as $tableName) {
                // Sanitize table name
                $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);

                if (empty($tableName)) {
                    continue;
                }

                $data = $this->getTableDataAsCSV($tableName, $limit);
                if (!empty($data)) {
                    $output .= $data . "\n\n";
                }
            }

            $response = ['success' => true, 'data' => trim($output)];
            echo json_encode($response);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Extracts table data in CSV format
     * @param string $table Table name
     * @param int $limit Maximum number of records to extract
     * @return string Data in CSV format
     */
    private function getTableDataAsCSV($table, $limit = 5)
    {
        try {
            // Get the connection via Adminer
            $connection = Adminer\connection();

            // Sanitize table name
            $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

            if (empty($table)) {
                return '';
            }

            // Check if the table exists
            $tables = array_keys(Adminer\tables_list());
            if (!in_array($table, $tables)) {
                return "## TABLE: $table\nTable not found\n";
            }

            // Get table fields
            $fields = Adminer\fields($table);
            if (empty($fields)) {
                return "## TABLE: $table (CSV Data)\nNo fields found\n";
            }

            $fieldNames = array_keys($fields);

            // Build the query
            $query = "SELECT " . implode(", ", array_map(function ($field) {
                return "`" . str_replace("`", "``", $field) . "`";
            }, $fieldNames)) . " FROM `" . str_replace("`", "``", $table) . "` LIMIT " . (int)$limit;

            // Execute the query
            $result = $connection->query($query);
            if (!$result) {
                return "## TABLE: $table (CSV Data)\nQuery error: " .
                       ($connection->error ?: 'Unknown error') . "\n";
            }

            // Build the CSV
            $csv = "## TABLE: $table\n";

            // CSV header
            $csv .= implode(",", array_map(function ($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $fieldNames)) . "\n";

            // Data rows
            $rowCount = 0;
            while ($row = $result->fetch_assoc()) {
                $csvRow = array();
                foreach ($fieldNames as $field) {
                    $value = $row[$field] ?? '';
                    // Mask sensitive data
                    $value = $this->maskSensitiveData($value, $field);

                    // Escape quotes and wrap in quotes if necessary
                    if (is_null($row[$field])) {
                        $csvRow[] = 'NULL';
                    } else {
                        $escapedValue = str_replace('"', '""', $value);
                        // Add quotes if value contains comma, newline, or quote
                        if (
                            strpos($value, ',') !== false ||
                            strpos($value, "\n") !== false ||
                            strpos($value, '"') !== false
                        ) {
                            $csvRow[] = '"' . $escapedValue . '"';
                        } else {
                            $csvRow[] = $escapedValue;
                        }
                    }
                }
                $csv .= implode(",", $csvRow) . "\n";
                $rowCount++;
            }

            if ($rowCount === 0) {
                $csv .= "(No data found)\n";
            }

            return $csv;
        } catch (Exception $e) {
            return "## TABLE: $table (CSV Data)\nError: " . $e->getMessage() . "\n";
        }
    }
}
