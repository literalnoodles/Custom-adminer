<?php

class AdminerAutocomplete
{
    public function debug($var)
    {
        echo '<pre>', var_dump($var), '</pre>';
        die();
    }

    public function head()
    {
        // if not sql editor page -> return
        if (!isset($_GET['sql'])) return;
        $old_sql = $_GET['sql'];
        $suggests = [];
        $suggest_fields = [];
        $keywords = [
            "DELETE FROM", "DISTINCT", "EXPLAIN", "FROM", "GROUP BY", "HAVING", "INSERT INTO", "INNER JOIN", "IGNORE",
            "LIMIT", "LEFT JOIN", "NULL", "ORDER BY", "ON DUPLICATE KEY UPDATE", "SELECT", "UPDATE", "WHERE"
        ];

        foreach (array_keys(tables_list()) as $table) {
            $suggests[] = $table;
            foreach (fields($table) as $field => $_) {
                $suggest_fields[] = "$table.$field";
            }
        }
?>
        <!-- html and js -->
        <style <?php echo nonce(); ?>>
            .ace_editor {
                width: 100%;
                height: 500px;
                resize: both;
                min-width: 600px;
            }

            #form {
                margin-top: 1rem;
                margin-right: 2rem;
            }

            #format-btn {
                vertical-align: middle;
                margin-left: 8px;
            }
        </style>
        <script <?php echo nonce(); ?> src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.28.0/ace.min.js" integrity="sha512-lWZC9X194C2FiYIgjYlYT8KHk1Ciqb0f2KTPp977eHyqRNgRKs3wsD6hTPZbaKMtl9WEiJwbcHoZ6L8K/dnu6A==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script <?php echo nonce(); ?> src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.28.0/ext-language_tools.min.js" integrity="sha512-e4CtsH8VPX0qJN80+doR277xcCGShw6pE53J0dJPgvH4EhFCw9PMxK0pvmy9zTtd0OO/4LtTanOEssioFwVpKQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script <?php echo nonce(); ?> src="https://cdnjs.cloudflare.com/ajax/libs/sql-formatter/13.0.0/sql-formatter.min.js" integrity="sha512-nKWckRTv5+B2ZtRVcCq3yfs4cHugABgfiUaEIbR9b9Kwr+4VLTtEDkB0nlpAHw1dmu9+ARoSGnxyIhpzA85qgw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script <?php echo nonce(); ?>>
            function autocomplete () {
                var keywords = <?php echo json_encode($keywords); ?>;
                var suggests = <?php echo json_encode($suggests); ?>;
                var suggest_fields = <?php echo json_encode($suggest_fields); ?>;
                var old_sql = `<?php echo $old_sql; ?>`;
                var textarea = document.querySelector('textarea.sqlarea');
                var form = document.querySelector('#form');
                var sql_area = document.createElement("div");
                var editor;
                form.insertBefore(sql_area, form.querySelector('p'));
                ace.config.set('basePath', 'ace-builds/src');

                editor = ace.edit(sql_area);
                editor.session.setMode('ace/mode/sql');
                // editor.setTheme('ace/theme/monokai');
                editor.setOptions({
                    fontSize: 14,
                    enableBasicAutocompletion: [{
                        identifierRegexps: [/[a-zA-Z_0-9\.\-\u00A2-\uFFFF]/], // added dot
                        getCompletions: (editor, session, pos, prefix, callback) => {
                            // note, won't fire if caret is at a word that does not have these letters
                            callback(null, [
                                ...keywords.map((word) => ({
                                    value: word + ' ',
                                    score: 3,
                                    meta: 'keyword'
                                })),
                                ...suggests.map((word) => ({
                                    value: word,
                                    score: 2,
                                    meta: 'name'
                                })),
                                ...suggest_fields.map((word) => ({
                                    value: word + ' ',
                                    score: 1,
                                    meta: 'field'
                                }))
                            ]);
                        },
                    }],
                    // to make popup appear automatically, without explicit ctrl+space
                    enableLiveAutocompletion: true,
                });
                document.querySelector("pre.sqlarea").hidden = true;
                if (old_sql) {
                    editor.setValue(old_sql, 1);
                };

                editor.getSession().on('change', () => {
                    textarea.value = editor.getSession().getValue();
                });

                return { editor };
            }

            function formatSql(editor) {
                var format_btn = document.createElement('button');
                format_btn.id = 'format-btn';
                format_btn.textContent = 'Format SQL';
                document.querySelector('input[value="Execute"][type="submit"]').after(format_btn);
                format_btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    formatted_sql = sqlFormatter.format(editor.getSession().getValue(), {
                        language: 'mysql',
                        tabWidth: 2,
                        keywordCase: 'upper',
                        linesBetweenQueries: 1,
                    });
                    editor.setValue(formatted_sql);
                });
            }

            document.addEventListener('DOMContentLoaded', () => {
                var { editor } = autocomplete();
                formatSql(editor);
                // overide focus function so it no longer display error
                old_focus = focus ?? (() => {});
                focus = (el) => {
                    if (el) old_focus(el);
                }
            })

        </script>
        <!-- end html and js -->
    <?php
    }
}
