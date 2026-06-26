// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Grade grid behaviour: colour indication, column fill, AJAX autosave.
 *
 * Plain JavaScript loaded via $PAGE->requires->js() so it needs no build step.
 *
 * @package mod_classjournal
 */
(function() {
    "use strict";

    var grid = document.getElementById("cj-grade-grid");
    if (!grid) {
        return;
    }
    var cmid = grid.getAttribute("data-cmid");
    var endpoint = M.cfg.wwwroot + "/mod/classjournal/ajax.php";

    /**
     * Colour a grade field by its share of the maximum.
     * @param {HTMLElement} field
     */
    function colour(field) {
        field.classList.remove("cj-empty", "cj-low", "cj-ok");
        var raw = field.value;
        if (raw === "" || raw === null) {
            field.classList.add("cj-empty");
            return;
        }
        var max = parseFloat(field.getAttribute("data-max"));
        var value = parseFloat(raw);
        if (isNaN(value) || isNaN(max) || max <= 0) {
            return;
        }
        field.classList.add((value / max) < 0.5 ? "cj-low" : "cj-ok");
    }

    /**
     * Find the comment field paired with a grade field.
     * @param {string} userid
     * @param {string} lessonid
     * @return {HTMLElement|null}
     */
    function commentFor(userid, lessonid) {
        return grid.querySelector(
            '.cj-comment[data-userid="' + userid + '"][data-lessonid="' + lessonid + '"]'
        );
    }

    /**
     * Persist one cell.
     * @param {HTMLElement} gradefield
     */
    function save(gradefield) {
        var userid = gradefield.getAttribute("data-userid");
        var lessonid = gradefield.getAttribute("data-lessonid");
        var commentfield = commentFor(userid, lessonid);
        var cell = gradefield.closest("td");

        var body = new URLSearchParams();
        body.set("id", cmid);
        body.set("lessonid", lessonid);
        body.set("userid", userid);
        body.set("grade", gradefield.value);
        body.set("comment", commentfield ? commentfield.value : "");
        body.set("sesskey", M.cfg.sesskey);

        fetch(endpoint, {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: body.toString(),
            credentials: "same-origin"
        }).then(function(response) {
            return response.ok ? response.json() : Promise.reject();
        }).then(function() {
            flash(cell, "cj-saved");
        }).catch(function() {
            flash(cell, "cj-saveerror");
        });
    }

    /**
     * Briefly flag a cell as saved or failed.
     * @param {HTMLElement} cell
     * @param {string} cls
     */
    function flash(cell, cls) {
        if (!cell) {
            return;
        }
        cell.classList.remove("cj-saved", "cj-saveerror");
        cell.classList.add(cls);
        window.setTimeout(function() {
            cell.classList.remove(cls);
        }, 1500);
    }

    grid.addEventListener("change", function(e) {
        var field = e.target;
        if (field.classList.contains("cj-grade")) {
            colour(field);
            save(field);
        } else if (field.classList.contains("cj-comment")) {
            var userid = field.getAttribute("data-userid");
            var lessonid = field.getAttribute("data-lessonid");
            var gradefield = grid.querySelector(
                '.cj-grade[data-userid="' + userid + '"][data-lessonid="' + lessonid + '"]'
            );
            if (gradefield) {
                save(gradefield);
            }
        }
    });

    // Fill every empty cell in a column with the first value already entered there.
    grid.addEventListener("click", function(e) {
        var button = e.target.closest(".cj-fill");
        if (!button) {
            return;
        }
        e.preventDefault();
        var lessonid = button.getAttribute("data-lessonid");
        var fields = grid.querySelectorAll('.cj-grade[data-lessonid="' + lessonid + '"]');
        var source = "";
        fields.forEach(function(f) {
            if (source === "" && f.value !== "") {
                source = f.value;
            }
        });
        if (source === "") {
            return;
        }
        fields.forEach(function(f) {
            if (f.value === "") {
                f.value = source;
                colour(f);
                save(f);
            }
        });
    });

    // Initial colouring.
    grid.querySelectorAll(".cj-grade").forEach(colour);
})();
