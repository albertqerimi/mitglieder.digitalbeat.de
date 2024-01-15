<?php
    $use_checkbox = ncore_isTrue( $exam->is_multiple_answer );
?>

<script>
ncoreJQ(document).ready( function(){

    ncoreJQ( '.dm_exam_answer_radio' ).change(function() {
        var val = ncoreJQ(this).val();

        ncoreJQ(this).closest('.dm_exam_question').find('.dm_exam_answer_radio').each(function(index,obj) {
            ncoreJQ(obj).parent('label').removeClass('dm_exam_selected');
        });

        ncoreJQ(this).parent('label').addClass('dm_exam_selected');
    });

    ncoreJQ( '.dm_exam_answer_checkbox' ).change(function() {

        var is_checked = ncoreJQ(this).is( ':checked' );

        if (is_checked) {
            ncoreJQ(this).parent('label').addClass('dm_exam_selected');
        }
        else
        {
            ncoreJQ(this).parent('label').removeClass('dm_exam_selected');
        }
    });
    ncoreJQ( '#dm_exam_<?=$exam->id?>_submit' ).click(function() {
        return confirm( "<?=_digi( 'Your answers will now be submitted.\n\nContinue?' ) ?>" );
    });
} );
</script>

<style>
.dm_exam_question {
    margin: 0 0 40px 0;
    box-sizing: border-box;
}

.dm_exam_question h3 {
    margin: 0;
    padding: 0;
}

.dm_exam_question > label {
    width: 100%;
    display: block;
    margin: 3px 0;
    padding: 3px 0;
    cursor: pointer;
    box-sizing: border-box;
}
.dm_exam_selected {
    background-color: rgb(217, 237, 247);
    border: 1px solid rgb(188, 232, 241);
}

/*
.dm_exam_result_correct {
    background-color: rgb(223, 240, 216);
    border: 1px solid rgb(214, 233, 198);
}

.dm_exam_result_error {
    background-color: rgb(242, 222, 222);
    border: 1px solid rgb(235, 204, 209);
}

.dm_exam_result_would_be_correct {
    background-color: rgb(217, 237, 247);
    border: 1px solid rgb(188, 232, 241);
}
*/
</style>

<?php if ($text): ?>
    <div class='digimember_exam_intro'><?=$text?></div>
<?php endif; ?>

<form method='post'>
<div class='dm_exam_container'>

<?php
    $postname = 'exam_'.$exam->id;

    foreach ($exam->questions as $question):
        if (empty($question['answer_count'])) {
            continue;
        }
?>
    <div class='dm_exam_question'>
        <h3><?=$question['hl']?></h3>
        <?php if ($question['descr']): ?>
            <div class='dm_exam_question_descr'><?=$question['descr']?></div>
        <?php endif; ?>
        <?php
            foreach ($question['answers'] as $i => $answer):

                if (empty($answer['text'])) {
                    continue;
                }

                $is_selected = !empty( $answer['is_selected'] );
                $css         = $is_selected
                             ? 'dm_exam_selected'
                             : '';
                $selected = $is_selected
                          ? 'checked="checked"'
                          : '';

                $type = $use_checkbox
                      ? 'checkbox'
                      : 'radio';
         ?>
            <label class='<?=$css?>'>
                <input <?=$selected?> class='dm_exam_answer_<?=$type?>' type='<?=$type?>' name="<?=$postname.'['.$question['id'].']'.($use_checkbox?'['.$i.']':'')?>" value="<?=$answer['id']?>" />
                <?=$answer['text'] ?>
            </label>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<button class='digimember_exam_button dm_exam_submit_button' id="dm_<?=$postname?>_submit" style="<?=$button_style?>"><?=_digi('Submit your answers')?></button>

</div>
</form>

