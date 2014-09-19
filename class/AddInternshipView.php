<?php

namespace Intern;

/*
 * View for interface to Add an Internship
 */
class AddInternshipView implements \View {

    private $terms;
    private $departments;

    public function __construct(Array $terms, Array $departments)
    {
        $this->terms = $terms;
        $this->departments = $departments;
    }

    public function render()
    {
        $tpl = array();

        // Translate departments into proper array format for template row repeat
        foreach ($this->departments as $id => $name) {
            $tpl['DEPARTMENTS'][] = array('DEPT_ID' => $id, 'DEPT_NAME' => $name);
        }

        // Translate terms into proper array format for template row repeat
        foreach ($this->terms as $term => $text) {
            $tpl['TERMS'][] = array('TERM' => $term, 'TERM_TEXT' => $text);
        }

        return \PHPWS_Template::process($tpl, 'intern', 'addInternship.tpl');
    }

    public function getContentType()
    {
        return 'text/html';
    }
}
?>