<div id="keyboard">
    <div id="keyboard-header">
        <div>
            <div class="ui-corner-all" id="keyboard-arrows-open">Arrows</div>
            <div class="ui-corner-all" id="keyboard-currency-open">Currency</div>
            <div class="ui-corner-all" id="keyboard-greek-open">Greek</div>
            <div class="ui-corner-all keyboard-header-active" id="keyboard-latin-open">Latin</div>
            <div class="ui-corner-all" id="keyboard-math-open">Math Symbols</div>
            <div class="ui-corner-all" id="keyboard-math2-open">Math Operators</div>
            <div class="ui-corner-all" id="keyboard-super-open">Superscripts & Subscripts</div>
            <div class="ui-corner-all" id="keyboard-technical-open">Technical</div>
            <div class="ui-corner-all" id="keyboard-other-open">Other</div>
        </div>
        <div id="keyboard-drag" class="ui-corner-all keyboard-header-active">
            <span class="fa fa-arrows"></span>
        </div>
        <div id="keyboard-close" class="ui-corner-all keyboard-header-active">
            <span class="fa fa-times-circle"></span>
        </div>
    </div>
    <div class="keyboard-content" id="keyboard-arrows">
        <?php
        for ($i = 8592; $i <= 8703; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        for ($i = 10224; $i <= 10239; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        for ($i = 10496; $i <= 10623; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        ?>
    </div>
    <div class="keyboard-content" id="keyboard-currency">
        <?php
        $chars = Array(36, 162, 163, 164, 165, 402, 1423, 2546, 2547,
            2801, 3065, 3647, 6107, 20803, 20870, 22278, 22286,
            22291, 22300, 65020, 65284, 65504, 65505, 65509, 65510);
        foreach ($chars as $char) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $char . ';';
            echo '</div> ';
        }
        for ($i = 8352; $i <= 8378; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        ?>
    </div>
    <div class="keyboard-content" id="keyboard-greek">
        <?php
        for ($i = 880; $i <= 887; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        for ($i = 890; $i <= 894; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        for ($i = 900; $i <= 906; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        echo ' <div class="ui-corner-all">';
        echo '&#908;';
        echo '</div> ';
        for ($i = 910; $i <= 929; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        for ($i = 931; $i <= 1023; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        ?>
    </div>
    <div class="keyboard-content" id="keyboard-latin" style="display:block">
        <?php
        for ($i = 192; $i <= 591; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        ?>
    </div>
    <div class="keyboard-content" id="keyboard-math">
        <?php
        for ($i = 119808; $i <= 119892; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        for ($i = 119894; $i <= 120485; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        for ($i = 120488; $i <= 120779; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        for ($i = 120782; $i <= 120831; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        ?>
    </div>
    <div class="keyboard-content" id="keyboard-math2">
        <?php
        echo ' <div class="ui-corner-all">';
        echo '&#172;';
        echo '</div> ';
        echo ' <div class="ui-corner-all">';
        echo '&#177;';
        echo '</div> ';
        for ($i = 8704; $i <= 8959; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        for ($i = 10176; $i <= 10223; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        for ($i = 10624; $i <= 11007; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        ?>
    </div>
    <div class="keyboard-content" id="keyboard-super">
        <?php
        for ($i = 8304; $i <= 8305; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        echo ' <div class="ui-corner-all">';
        echo '&#185;';
        echo '</div> ';
        echo ' <div class="ui-corner-all">';
        echo '&#178;';
        echo '</div> ';
        echo ' <div class="ui-corner-all">';
        echo '&#179;';
        echo '</div> ';
        for ($i = 8308; $i <= 8334; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        for ($i = 8336; $i <= 8348; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        ?>
    </div>
    <div class="keyboard-content" id="keyboard-technical">
        <?php
        for ($i = 8960; $i <= 9203; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        ?>
    </div>
    <div class="keyboard-content" id="keyboard-other">
        <?php
        for ($i = 134; $i <= 135; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        echo ' <div class="ui-corner-all">';
        echo '&#137;';
        echo '</div> ';
        echo ' <div class="ui-corner-all">';
        echo '&#151;';
        echo '</div> ';
        echo ' <div class="ui-corner-all">';
        echo '&#153;';
        echo '</div> ';
        echo ' <div class="ui-corner-all">';
        echo '&#169;';
        echo '</div> ';
        echo ' <div class="ui-corner-all">';
        echo '&#174;';
        echo '</div> ';
        for ($i = 8448; $i <= 8585; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        for ($i = 188; $i <= 190; $i++) {
            echo ' <div class="ui-corner-all">';
            echo '&#' . $i . ';';
            echo '</div> ';
        }
        ?>
    </div>
</div>