<?php
use Drupal\ClassLearning\Models\User,
  Drupal\ClassLearning\Models\WorkflowTask,
  Drupal\ClassLearning\Models\Assignment,
  Drupal\ClassLearning\Models\AssignmentSection,
  Drupal\ClassLearning\Common\Accordion,
  Drupal\ClassLearning\Models\Section,
  Drupal\ClassLearning\Models\SectionUsers,
  Drupal\ClassLearning\Models\Semester,
  Drupal\ClassLearning\Models\Workflow;

function groupgrade_assignments_dashboard() {
  global $user;
  $assignments = User::assignedAssignments()->get();

  $return = '';
  $rows = [];
  $headers = ['Assignment', 'Course', 'Start Date'];

  if (count($assignments) > 0) : foreach ($assignments as $assignment) :
    $rows[] = [
      sprintf('<a href="%s">%s</a>', url('class/assignments/'.$assignment->section_id.'/'.$assignment->asec_id), $assignment->assignment_title),
      sprintf('%s &mdash; %s', $assignment->course_name, $assignment->section_name),
      $assignment->asec_start
    ];
  endforeach; endif;

  $return .= sprintf('<p>%s<p>',
    t('Select an assignment title to see the problems created for that assignment. Note that you might not be allowed to see some work in progress.')
  );

  $return .= theme('table', array(
    'header' => $headers, 
    'rows' => $rows,
    'empty' => t('No assignments found.'),
    'attributes' => array('width' => '100%')
  ));

  return $return;
}

function gg_view_assignment_listing($section_id, $asec_id)
{
  if (! gg_in_section($section_id))
    return drupal_not_found();
  
  $asec = AssignmentSection::find($asec_id);
  $section = $asec->section()->first();

  if ((int) $section->section_id !== (int) $section_id)
    return drupal_not_found();

  $assignment = $asec->assignment()->first();
  
  drupal_set_title($assignment->assignment_title);

  $createProblems = WorkflowTask::whereIn('workflow_id', function($query) use ($asec_id)
  {
    $query->select('workflow_id')
      ->from('workflow')
      ->where('assignment_id', '=', $asec_id);
  })
    ->whereType('edit problem')
    ->whereStatus('complete')
    ->get();

  $headers = ['Problem'];
  $rows = [];

  if (count($createProblems) > 0) : foreach ($createProblems as $t) :
    $rows[] = [sprintf(
      '<a href="%s">%s</a>',
      url('class/workflow/'.$t->workflow_id),
      word_limiter($t->data['problem'], 20)
    )];
  endforeach; endif;

  $return = '';

  // Back Link
  $return .= sprintf('<p><a href="%s">%s %s</a>', url('class/assignments'), HTML_BACK_ARROW, t('Back to Assignment List in Everyone\'s Work'));

  // Course/section/semester
  $course = $section->course()->first();
  $semester = $section->semester()->first();

  $return .= sprintf('<p><strong>%s</strong>: %s &mdash; %s &mdash; %s',
    t('Course'),
    $course->course_name,
    $section->section_name,
    $semester->semester_name
  );

  // Assignment Description
  $return .= sprintf('<p class="summary">%s</p>', nl2br($assignment->assignment_description));
  $return .= '<hr />';
    
  // Instructions
  $return .= sprintf('<p>%s <em>%s</em><p>',
    t('Select a question to see the work on that question so far.'),
    t('Note that you will not be allowed to see some work in progress.')
  );

  $return .= theme('table', array(
    'header' => $headers, 
    'rows' => $rows,
    'empty' => 'No problems found.',
    'attributes' => array('width' => '100%')
  ));

  return $return;
}

function groupgrade_createproblem_dash() {
  global $user;

  drupal_set_title(t('Assignment Editor'));

  $assignment_ed = Assignment::where('user_ids', '=', $users->uids)
    ->orderBy('assign_id', 'desc')
    ->get();

  $return = '';
  $return .= '<h3>Assignment Editor</h3>';
  $return .= sprintf('<p><a href="%s">%s</a></p>', url('class/assignmentseditor'), t('Create Assignment'));
  $return .= sprintf('<p>%s</p>', t('Select "View" to manage an existing assignment: edit it, assign it to or remove it from a section, change its start date, etc.'));
  
  $rows = array();

  if (count($assignment_ed) > 0) : foreach($assignment_ed as $assignee) :
    $rows[] = array($assignee->assignment_name,
        '<a href="'.url('class/instructor/class/'.$assignee->assign_id).'">View</a>');
  endforeach; endif;

  $return .= theme('table', array(
    'rows' => $rows,
    'header' => array('Title', '# of Sections', 'Operations'),
    'empty' => 'No assignments found.',
    'attributes' => array('width' => '100%'),
  ));

  return $return;
}

function groupgrade_form1(){

	$form = drupal_get_form('groupgrade_createproblem');

	return $form;	
		
}
function groupgrade_createproblem($form, &$form_state){
  
  $form = array();
  $form[] = [
	  '#markup' => t('<h2><font color = "#A8A8A8"> Assignment Editor </font></h2>'),
	 ];

  $form['tasks'] = array(
    '#type' => 'fieldset',
    '#collapsible' => FALSE,
  );
  $form['tasks']['p1'] = array(
    '#type' => 'fieldset',
    '#title' => t('Create Problem'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#prefix' => '<div style="margin-bottom:50px">',
    '#suffix' => '</div>',
  );
  
// automated

  $form['tasks']['p1']['TA_type'] = array(
    '#value' => 'create problem',
    '#type' => 'hidden',
    '#collapsible' => FALSE,
  );

// Basic

  $form['tasks']['p1']['basic'] = array(
    '#type' => 'fieldset',
    '#title' => t('<font color = "#980000"> Basic </font>'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
  );   
  $form['tasks']['p1']['basic']['p1-TA_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Task Assignment Name:'),
    //'#required' => TRUE,
    '#default_value' => "Create Problem",
    '#description' => "Please enter Task Assignment Name.",
    //'#size' => 20,
    //'#maxlength' => 20,
  );
  $form['tasks']['p1']['basic']['p1-A_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Assignment Name:'),
    //'#required' => TRUE,
    '#default_value' => "Assignment 1",
    '#description' => "Please enter Assignment Name.",
    //'#size' => 20,
    //'#maxlength' => 20,
  );
  
  $form['tasks']['p1']['basic']['p1-A_type'] = array(
       '#type' => 'select',
       '#default_value' => variable_get("Homework", true),
       '#title' => t('type of assignment?'),
       '#options' => array(
         'homework' => t('Homework'),
         'exam' => t('Exam'),
         'quiz' => t('Quiz'),
         'lab' => t('Lab'),
		 ),
       //'#default_value' => 0,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
  $form['tasks']['p1']['basic']['p1-TA_due'] =array(
	   '#type' => 'select',
       '#title' => t('When is task due?'),
       '#options' => array(
         0 => t('# of days after last task completes'),
         1 => t('Specific Date'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option 0 
   $form['tasks']['p1']['basic']['p1-TA_due_select'] =array(
       '#type' => 'textfield',
       '#title' => 'Enter days:',
       '#default_value' => '3',
       '#states' => array(
			'visible' => array(
			':input[name="p1-TA_due"]' => array('value' => 0),
		),
	),
	);
   // option 1
   $form['tasks']['p1']['basic']['p1-TA_due_date_select'] =array(
	'#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Specific Date:'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="p1-TA_due"]' => array('value' => 1),
		),
	),
	);

// Advanced

   $form['tasks']['p1']['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('<font color = "#980000"> Advanced </font>'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['p1']['advanced']['p1-TA_start_time'] =array(
     '#type' => 'select',
     '#title' => t('Start when prior task completes?'),
     '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
   //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option "NO"
   $form['tasks']['p1']['advanced']['p1-TA_start_time date_select'] =array(
	   '#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Start no earlier than specific date/time'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="p1-TA_start_time"]' => array('value' => 1),
		),
	),
   );
    $form['tasks']['p1']['advanced']['p1-TA_at_duration_end'] =array(
	   '#type' => 'select',
       '#title' => t('Task status at duration end'),
       '#options' => array(
         0 => t('Late'),
         1 => t('Complete'),
         2 => t('Resolved'),
		 ),
       '#default_value' => 0,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
    $form['tasks']['p1']['advanced']['p1-TA_what_if_late'] =array(
	   '#type' => 'select',
       '#title' => t('What if late?'),
       '#options' => array(
         0 => t('Keep same participant'),
         1 => t('Allocate new participant'),
         2 => t('Allocate to instructor'),
         3 => t('Allocate to different group member'),
	 4 => t('Consider resolved'),
		 ),
       '#default_value' => 'Keep same participant',
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p1']['advanced']['p1-TA_display_name'] = array(
   	'#type' => 'textfield',
    	'#title' => t('Task Assignment Display Name:'),
    	//'#required' => TRUE,
    	'#default_value' => "Create Problem",
    	'#description' => "Please enter Task Assignment Display Name.",	
   );
   $form['tasks']['p1']['advanced']['p1-TA_description'] = array(
   		'#type' => 'textarea',
    	'#title' => t('Description'),
    	//'#required' => FALSE,
    	'#description' => "Please enter Description",	
   );
   $form['tasks']['p1']['advanced']['p1-TA_one_or_seperate'] = array(
       '#type' => 'select',
       '#title' => t('Everyone gets same problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
  );
   $form['tasks']['p1']['advanced']['p1-TA_assignee_constraints'] = array(
       '#type' => 'select',
       '#title' => t('Who does?'),
       '#options' => array(
         0 => t('Student'),
         1 => t('Instructor'),
		 ),   
  );

// option 0

   $form['tasks']['p1']['advanced']['p1-TA_assignee_constraints_select'] = array(
        '#type' => 'radios',
        '#title' => 'Student does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="p1-TA_assignee_constraints"]' => array('value' => 0),
		),
	),
	);

   // option 1
   
   $form['tasks']['p1']['advanced']['p1-TA_assignee_constraints_select1'] = array(
        '#type' => 'radios',
        '#title' => 'Instructor does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="p1-TA_assignee_constraints"]' => array('value' => 1),
		),
	),
	);
   $form['tasks']['p1']['advanced']['p1-TA_function_type'] = array(
	
   );

// template

   $form['tasks']['p1']['template'] = array(
    '#type' => 'fieldset',
    '#title' => t('<font color = "#980000"> Template </font>'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['p1']['template']['p1-TA_instructions'] = array(
   	'#type' => 'textarea',
    '#title' => t('Instructions'),
    //'#required' => TRUE,
    '#default_value' => 'Read the assignment instructions and enter '
          .'a problem in the box below. Make your problem as clear as '
          .'possible so the person solving it will understand what you mean. '
          .'This solution is graded out of 100 points.',
    '#description' => "Please enter instructions.",	
   );
   $form['tasks']['p1']['template']['p1-TA_rubric'] = array(
   	'#type' => 'textarea',
    '#title' => t('Rubric'),
    //'#required' => TRUE,
    '#default_value' => " ",
    	'#description' => "Please enter Rubric",	
   );


// Supplemental 

  $form['tasks']['p1']['supplemental'] = array(
    '#type' => 'fieldset',
    '#title' => t('<font color = "#980000"> Supplemental </font>'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );   
   $form['tasks']['p1']['supplemental']['p1-TA_allow_edit+comment'] = array(
       '#type' => 'select',
       '#title' => t('Edit & comment?'),
       '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
       '#description' => t('Choose One'),
   );
   $form['tasks']['p1']['supplemental']['p1-TA_allow_comment_only'] = array(
       '#type' => 'select',
       '#title' => t('Comment only?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       '#description' => t('Choose One'),
	   );
   $form['tasks']['p1']['supplemental']['p1-TA_allow_revisions'] = array(
       '#type' => 'select',
       '#title' => t('Optionally send back for revisions?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p1']['supplemental']['p1-TA_allow_grade'] = array(
       '#type' => 'select',
       '#title' => t('Allow Grade?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p1']['supplemental']['p1-TA_number_participants'] = array(
       '#type' => 'hidden',
       '#title' => t('Number of participants'),
       '#options' => array(
         0 => t('1'),
         1 => t('2'),
         2 => t('3')
		 ),
		 '#default_value' => 1,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );	

   $form['tasks']['p1']['supplemental']['p1-TA_allow_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow Dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
   $form['tasks']['p1']['supplemental']['p1-TA_allow_resolve_grades'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve grades?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
   $form['tasks']['p1']['supplemental']['p1-TA_allow_resolve_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
	$form['tasks']['p1']['supplemental']['p1-TA_leads_to_new_problem'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
	$form['tasks']['p1']['supplemental']['p1-TA_leads_to_new_solution'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new solution'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);


// Automated

  $form['tasks']['p1']['TA_visual_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1']['TA_Id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1']['TA_WA_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1']['TA_A_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1']['TA_version_history'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1']['TA_refers_to_which_task'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1']['TA_trigger_condition'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1']['TA_next_task'] = array(
	'#type' => 'hidden',
  );
   
//************************************************************
//	Create Problem ---- p1.1 ==> Edit & Comment
//************************************************************
/*

   	$form['tasks']['p11'] = array(
    '#type' => 'fieldset',
    '#title' => t('<font color = "336666"> Edit & Comment </font>'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:15px">',
    '#suffix' => '</div>',
    '#states' => array(
		'visible' => array(
		':input[name="p1-TA_allow_edit+comment"]' => array('value' => 0),
		),
	),
   );	  
   
   $form['tasks']['p11']['TA_type'] = array(
  	'#value' => 'edit & comment',
    	'#type' => 'hidden',
    	'#collapsible' => FALSE,
    );
   $form['tasks']['p11']['use'] = array(
    '#value' => TRUE,
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );
	$form['tasks']['p11']['basic'] = array(
    '#type' => 'fieldset',
    '#title' => t('Basic'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
  );   
  $form['tasks']['p11']['basic']['p11-TA_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Task Assignment Name:'),
    '#required' => TRUE,
    '#default_value' => "Edit & Comment",
    '#description' => "Please enter Task Assignment Name.",
  );
  $form['tasks']['p11']['basic']['p11-TA_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Task Assignment Name:'),
    '#required' => TRUE,
    '#default_value' => "Edit & Comment",
    '#description' => "Please enter Task Assignment Name.",
  );
  $form['tasks']['p11']['basic']['p11-TA_due'] =array(
       '#type' => 'select',
       '#title' => t('When is task due?'),
       '#options' => array(
         0 => t('# of days after last task completes'),
         1 => t('Specific Date'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option 0 
   $form['tasks']['p11']['basic']['p11-TA_due_select'] =array(
       '#type' => 'textfield',
       '#title' => 'Enter days:',
       '#default_value' => '3',
       '#states' => array(
			'visible' => array(
			':input[name="p11-TA_due"]' => array('value' => 0),
		),
	),
	);
   // option 1
   $form['tasks']['p11']['basic']['p11-TA_due_date_select'] =array(
	'#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Specific Date:'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="p11-TA_due"]' => array('value' => 1),
		),
	),
	);

// Advanced

   $form['tasks']['p11']['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['p11']['advanced']['p11-TA_start_time'] =array(
     '#type' => 'select',
     '#title' => t('Start when prior task completes?'),
     '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
   //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option "NO"
   $form['tasks']['p11']['advanced']['p11-TA_start_time date_select'] =array(
	   '#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Start no earlier than specific date/time'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="p11-TA_start_time"]' => array('value' => 1),
		),
	),
   );
    $form['tasks']['p11']['advanced']['p11-TA_at_duration_end'] =array(
	   '#type' => 'select',
       '#title' => t('Task status at duration end'),
       '#options' => array(
         0 => t('Late'),
         1 => t('Complete'),
         2 => t('Resolved'),
		 ),
       '#default_value' => 0,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
    $form['tasks']['p11']['advanced']['p11-TA_what_if_late'] =array(
       '#type' => 'select',
       '#title' => t('What if late?'),
       '#options' => array(
         0 => t('Keep same participant'),
         1 => t('Allocate new participant'),
         2 => t('Allocate to instructor'),
         3 => t('Allocate to different group member'),
	 4 => t('Consider resolved'),
		 ),
       '#default_value' => 'Keep same participant',
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p11']['advanced']['p11-TA_display_name'] = array(
   	'#type' => 'textfield',
    	'#title' => t('Task Assignment Display Name:'),
    	'#required' => TRUE,
    	'#default_value' => "Create Problem",
    	'#description' => "Please enter Task Assignment Display Name.",	
   );
   $form['tasks']['p11']['advanced']['p11-TA_description'] = array(
   	'#type' => 'textarea',
    	'#title' => t('Description'),
    	'#required' => FALSE,
    	'#description' => "Please enter Description",	
   );
   
   $form['tasks']['p11']['advanced']['p11-TA_one_or_seperate'] = array(
       '#type' => 'hidden',
       '#value' => 0,
	
  );
    
   $form['tasks']['p11']['advanced']['p11-TA_assignee_constraints'] = array(
       '#type' => 'select',
       '#title' => t('Who does?'),
       '#options' => array(
         0 => t('Student'),
         1 => t('Instructor'),
		 ),
        '#default_value' => 1,		    
  );

// option 0

   $form['tasks']['p11']['advanced']['p11-TA_assignee_constraints select'] = array(
        '#type' => 'radios',
        '#title' => 'Student does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="p11-TA_assignee_constraints"]' => array('value' => 0),
		),
	),
	);

   // option 1
   
   $form['tasks']['p11']['advanced']['p11-TA_assignee_constraints select1'] = array(
        '#type' => 'radios',
        '#title' => 'Instructor does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 1,
    	'#states' => array(
		'visible' => array(
		':input[name="p11-TA_assignee_constraints"]' => array('value' => 1),
		),
	),
	);
   $form['tasks']['p11']['advanced']['p11-TA_function_type'] = array(
	
   );

// template

   $form['tasks']['p11']['template'] = array(
    '#type' => 'fieldset',
    '#title' => t('Template'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['p11']['template']['p11-TA_instructions'] = array(
   	'#type' => 'textarea',
    '#title' => t('Instructions'),
    '#required' => TRUE,
    '#default_value' => 'Rephrase the problem (if necessary) so it is '
          .'appropriate to the assignment and clear to the person solving '
          .'it. The solver and graders will only see your edited version, not '
          .'the original version. (Others not involved in solving or grading '
          .'will see both the original and edited versions.) You can also '
          .'leave a comment to explain any rephrasing.',
    '#description' => "Please enter instructions.",	
   );
   $form['tasks']['p11']['template']['p11-TA_rubric'] = array(
   	'#type' => 'textarea',
    '#title' => t('Rubric for edit & comment'),
    '#default_value' => " ",
    	'#description' => "Please enter Rubric",	
   );


// Supplemental 

  $form['tasks']['p11']['supplemental'] = array(
    '#type' => 'fieldset',
    '#title' => t('Supplemental'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );   
   $form['tasks']['p11']['supplemental']['p11-TA_allow_edit+comment'] = array(
       '#type' => 'select',
       '#title' => t('Edit & comment?'),
       '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
       '#description' => t('Choose One'),
   );
   $form['tasks']['p11']['supplemental']['p11-TA_allow_comment_only'] = array(
       '#type' => 'select',
       '#title' => t('Comment only?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       '#description' => t('Choose One'),
	   );
   $form['tasks']['p11']['supplemental']['p11-TA_allow_revisions'] = array(
       '#type' => 'select',
       '#title' => t('Optionally send back for revisions?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p11']['supplemental']['p11-TA_allow_grade'] = array(
       '#type' => 'select',
       '#title' => t('Allow Grade?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p11']['supplemental']['p11-TA_number_participants'] = array(
       '#type' => 'hidden',
       '#title' => t('Number of participants'),
       '#options' => array(
         0 => t('1'),
         1 => t('2'),
         2 => t('3')
		 ),
		 '#default_value' => 1,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );	


   $form['tasks']['p11']['supplemental']['p11-TA_allow_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow Dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
   $form['tasks']['p11']['supplemental']['p11-TA_allow_resolve_grades'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve grades?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
   $form['tasks']['p11']['supplemental']['p11-TA_allow_resolve_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
	
);
	$form['tasks']['p11']['supplemental']['p11-TA_leads_to_new_problem'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
	$form['tasks']['p11']['supplemental']['p11-TA_leads_to_new_solution'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new solution'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);


// Automated

  $form['tasks']['p11']['TA_visual_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p11']['TA_Id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p11']['TA_WA_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p11']['TA_A_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p11']['TA_version_history'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p11']['TA_refers_to_which_task'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p11']['TA_trigger_condition'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p11']['TA_next_task'] = array(
	'#type' => 'hidden',
  );


 //************************************************************
 //	Create Problem ---- p1.2 ==> Comment Only 
 //************************************************************

	$form['tasks']['p1.2'] = array(
    '#type' => 'fieldset',
    '#title' => t('Comment Only'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px">',
    '#suffix' => '</div>',
    '#states' => array(
		'visible' => array(
		':input[name="p1-TA_allow_comment_only"]' => array('value' => 1),
		),
	),
   ); 
    $form['tasks']['p1.2']['TA_type'] = array(
  	'#value' => 'comment only',
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );
	
	$form['tasks']['p1.2']['use'] = array(
  	'#value' => TRUE,
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );
	$form['tasks']['p1.2']['basic'] = array(
    '#type' => 'fieldset',
    '#title' => t('Basic'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
  );   
  $form['tasks']['p1.2']['basic']['p1.2-TA_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Task Assignment Name:'),
    '#required' => TRUE,
    '#default_value' => "Comment Only",
    '#description' => "Please enter Task Assignment Name.",
    //'#size' => 20,
    //'#maxlength' => 20,
  );
  $form['tasks']['p1.2']['basic']['p1.2-TA_due'] =array(
       '#type' => 'select',
       '#title' => t('When is task due?'),
       '#options' => array(
         0 => t('# of days after last task completes'),
         1 => t('Specific Date'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option 0 
   $form['tasks']['p1.2']['basic']['p1.2-TA_due select'] =array(
       '#type' => 'textfield',
       '#title' => 'Enter days:',
       '#default_value' => '3',
       '#states' => array(
			'visible' => array(
			':input[name="p1.2-TA_due"]' => array('value' => 0),
		),
	),
	);
   // option 1
   $form['tasks']['p1.2']['basic']['p1.2-TA_due date_select'] =array(
	'#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Specific Date:'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="p1.2-TA_due"]' => array('value' => 1),
		),
	),
	);

// Advanced

   $form['tasks']['p1.2']['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['p1.2']['advanced']['p1.2-TA_start_time'] =array(
     '#type' => 'select',
     '#title' => t('Start when prior task completes?'),
     '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
   //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option "NO"
   $form['tasks']['p1.2']['advanced']['p1.2-TA_start_time date_select'] =array(
	   '#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Start no earlier than specific date/time'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="p1.2-TA_start_time"]' => array('value' => 1),
		),
	),
   );
    $form['tasks']['p1.2']['advanced']['p1.2-TA_at_duration_end'] =array(
	   '#type' => 'select',
       '#title' => t('Task status at duration end'),
       '#options' => array(
         0 => t('Late'),
         1 => t('Complete'),
         2 => t('Resolved'),
		 ),
       '#default_value' => 0,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
    $form['tasks']['p1.2']['advanced']['p1.2-TA_what_if_late'] =array(
       '#type' => 'select',
       '#title' => t('What if late?'),
       '#options' => array(
         0 => t('Keep same participant'),
         1 => t('Allocate new participant'),
         2 => t('Allocate to instructor'),
         3 => t('Allocate to different group member'),
		 4 => t('Consider resolved'),
		 ),
       '#default_value' => 'Keep same participant',
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p1.2']['advanced']['p1.2-TA_display_name'] = array(
   	'#type' => 'textfield',
    	'#title' => t('Task Assignment Display Name:'),
    	'#required' => TRUE,
    	'#default_value' => "Create Problem",
    	'#description' => "Please enter Task Assignment Display Name.",	
   );
   $form['tasks']['p1.2']['advanced']['p1.2-TA_description'] = array(
   	'#type' => 'textarea',
    	'#title' => t('Description'),
    	'#required' => FALSE,
    	'#description' => "Please enter Description",	
   );
   $form['tasks']['p1.2']['advanced']['p1.2-TA_one_or_seperate'] = array(
       '#type' => 'hidden',
       '#title' => t('Everyone gets same problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	   '#default_value' => 0,
  );
   $form['tasks']['p1.2']['advanced']['p1.2-TA_assignee_constraints'] = array(
       '#type' => 'select',
       '#title' => t('Who does?'),
       '#options' => array(
         0 => t('Student'),
         1 => t('Instructor'),
		 ),  
	   '#default_value' => 1, 
  );

// option 0

   $form['tasks']['p1.2']['advanced']['p1.2-TA_assignee_constraints select'] = array(
        '#type' => 'radios',
        '#title' => 'Student does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="p1.2-TA_assignee_constraints"]' => array('value' => 0),
		),
	),
	);

   // option 1
   
   $form['tasks']['p1.2']['advanced']['p1.2-TA_assignee_constraints select1'] = array(
        '#type' => 'radios',
        '#title' => 'Instructor does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 1,
    	'#states' => array(
		'visible' => array(
		':input[name="p1.2-TA_assignee_constraints"]' => array('value' => 1),
		),
	),
	);
   $form['tasks']['p1.2']['advanced']['p1.2-TA_function_type'] = array(
	
   );

// template

   $form['tasks']['p1.2']['template'] = array(
    '#type' => 'fieldset',
    '#title' => t('Template'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['p1.2']['template']['p1.2-TA_instructions'] = array(
   	'#type' => 'textarea',
    '#title' => t('Instructions'),
    '#required' => TRUE,
    '#default_value' => 'TBD',
    '#description' => "Please enter instructions.",	
   );
   $form['tasks']['p1.2']['template']['p1.2-TA_rubric'] = array(
   	'#type' => 'textarea',
    '#title' => t('Rubric for comment only'),
    '#required' => TRUE,
    '#default_value' => " ",
    	'#description' => "Please enter Rubric",	
   );


// Supplemental 

  $form['tasks']['p1.2']['supplemental'] = array(
    '#type' => 'fieldset',
    '#title' => t('Supplemental'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );   
   $form['tasks']['p1.2']['supplemental']['p1.2-TA_allow_edit+comment'] = array(
       '#type' => 'select',
       '#title' => t('Edit & comment?'),
       '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
       '#description' => t('Choose One'),
   );
   $form['tasks']['p1.2']['supplemental']['p1.2-TA_allow_comment_only'] = array(
       '#type' => 'select',
       '#title' => t('Comment only?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       '#description' => t('Choose One'),
	   );
   $form['tasks']['p1.2']['supplemental']['p1.2-TA_allow_revisions'] = array(
       '#type' => 'select',
       '#title' => t('Optionally send back for revisions?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p1.2']['supplemental']['p1.2-TA_allow_grade'] = array(
       '#type' => 'select',
       '#title' => t('Allow Grade?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p1.2']['supplemental']['p1.2-TA_number_participants'] = array(
       '#type' => 'hidden',
       '#title' => t('Number of participants'),
       '#options' => array(
         0 => t('1'),
         1 => t('2'),
         2 => t('3')
		 ),
		 '#default_value' =>0,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );	


   $form['tasks']['p1.2']['supplemental']['p1.2-TA_allow_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow Dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
   $form['tasks']['p1.2']['supplemental']['p1.2-TA_allow_resolve_grades'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve grades?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
   $form['tasks']['p1.2']['supplemental']['p1.2-TA_allow_resolve_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
	$form['tasks']['p1.2']['supplemental']['p1.2-TA_leads_to_new_problem'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
	$form['tasks']['p1.2']['supplemental']['p1.2-TA_leads_to_new_solution'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new solution'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);


// Automated

  $form['tasks']['p1.2']['TA_visual_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.2']['TA_Id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.2']['TA_WA_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.2']['TA_A_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.2']['TA_version_history'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.2']['TA_refers_to_which_task'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.2']['TA_trigger_condition'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.2']['TA_next_task'] = array(
	'#type' => 'hidden',
  );


 //************************************************************
 //	Create Problem ---- p1.3 ==> Revise & Resubmit
 //************************************************************
 
	$form['tasks']['p1.3'] = array(
    '#type' => 'fieldset',
    '#title' => t('Revise & Resubmit'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px">',
    '#suffix' => '</div>',
    '#states' => array(
		'visible' => array(
		':input[name="p1-TA_allow_revisions"]' => array('value' => 1),
		),
	),
   ); 
    $form['tasks']['p1.3']['TA_type'] = array(
  	'#value' => 'revise & resubmit',
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );

	$form['tasks']['p1.3']['use'] = array(
  	'#value' => TRUE,
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );
	$form['tasks']['p1.3']['basic'] = array(
    '#type' => 'fieldset',
    '#title' => t('Basic'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
  );   
  $form['tasks']['p1.3']['basic']['p1.3-TA_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Task Assignment Name:'),
    '#required' => TRUE,
    '#default_value' => "Revise & Resubmit",
    '#description' => "Please enter Task Assignment Name.",
    //'#size' => 20,
    //'#maxlength' => 20,
  );
  
 $form['tasks']['p1.3']['basic']['p1.3-TA_due'] =array(
       '#type' => 'select',
       '#title' => t('When is task due?'),
       '#options' => array(
         0 => t('# of days after last task completes'),
         1 => t('Specific Date'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option 0 
   $form['tasks']['p1.3']['basic']['p1.3-TA_due select'] =array(
       '#type' => 'textfield',
       '#title' => 'Enter days:',
       '#default_value' => '3',
       '#states' => array(
			'visible' => array(
			':input[name="p1.3-TA_due"]' => array('value' => 0),
		),
	),
	);
   // option 1
   $form['tasks']['p1.3']['basic']['p1.3-TA_due date_select'] =array(
	'#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Specific Date:'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="p1.3-TA_due"]' => array('value' => 1),
		),
	),
	);

// Advanced

   $form['tasks']['p1.3']['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['p1.3']['advanced']['p1.3-TA_start_time'] =array(
     '#type' => 'select',
     '#title' => t('Start when prior task completes?'),
     '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
   //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option "NO"
   $form['tasks']['p1.3']['advanced']['p1.3-TA_start_time date_select'] =array(
	   '#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Start no earlier than specific date/time'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="p1.3-TA_start_time"]' => array('value' => 1),
		),
	),
   );
    $form['tasks']['p1.3']['advanced']['p1.3-TA_at_duration_end'] =array(
	   '#type' => 'select',
       '#title' => t('Task status at duration end'),
       '#options' => array(
         0 => t('Late'),
         1 => t('Complete'),
         2 => t('Resolved'),
		 ),
       '#default_value' => 1,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
    $form['tasks']['p1.3']['advanced']['p1.3-TA_what_if_late'] =array(
       '#type' => 'select',
       '#title' => t('What if late?'),
       '#options' => array(
         0 => t('Keep same participant'),
         1 => t('Allocate new participant'),
         2 => t('Allocate to instructor'),
         3 => t('Allocate to different group member'),
		 4 => t('Consider resolved'),
		 ),
       '#default_value' => 4,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p1.3']['advanced']['p1.3-TA_display_name'] = array(
   	'#type' => 'textfield',
    	'#title' => t('Task Assignment Display Name:'),
    	'#required' => TRUE,
    	'#default_value' => "Create Problem",
    	'#description' => "Please enter Task Assignment Display Name.",	
   );
   $form['tasks']['p1.3']['advanced']['p1.3-TA_description'] = array(
   	'#type' => 'textarea',
    	'#title' => t('Description'),
    	'#required' => FALSE,
    	'#description' => "Please enter Description",	
   );
   $form['tasks']['p1.3']['advanced']['p1.3-TA_one_or_seperate'] = array(
       '#type' => 'hidden',
       '#title' => t('Everyone gets same problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	   '#default_value' => 0,
  );
   $form['tasks']['p1.3']['advanced']['p1.3-TA_assignee_constraints'] = array(
       '#type' => 'select',
       '#title' => t('Who does?'),
       '#options' => array(
         0 => t('Student'),
         1 => t('Instructor'),
		 ),  
	   '#default_value' => 0, 
  );

// option 0

   $form['tasks']['p1.3']['advanced']['p1.3-TA_assignee_constraints select'] = array(
        '#type' => 'radios',
        '#title' => 'Student does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="p1.3-TA_assignee_constraints"]' => array('value' => 0),
		),
	),
	);

   // option 1
   
   $form['tasks']['p1.3']['advanced']['p1.3-TA_assignee_constraints select1'] = array(
        '#type' => 'radios',
        '#title' => 'Instructor does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 1,
    	'#states' => array(
		'visible' => array(
		':input[name="p1.3-TA_assignee_constraints"]' => array('value' => 1),
		),
	),
	);
   $form['tasks']['p1.3']['advanced']['p1.3-TA_function_type'] = array(
	
   );

// template

   $form['tasks']['p1.3']['template'] = array(
    '#type' => 'fieldset',
    '#title' => t('Template'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['p1.3']['template']['p1.3-TA_instructions'] = array(
   	'#type' => 'textarea',
    '#title' => t('Instructions'),
    '#required' => TRUE,
    '#default_value' => 'TBD',
    '#description' => "Please enter instructions.",	
   );
   $form['tasks']['p1.3']['template']['p1.3-TA_rubric'] = array(
   	'#type' => 'textarea',
    '#title' => t('Rubric for revise & resubmit'),
    '#required' => TRUE,
    '#default_value' => " ",
    	'#description' => "Please enter Rubric",	
   );


// Supplemental 

  $form['tasks']['p1.3']['supplemental'] = array(
    '#type' => 'fieldset',
    '#title' => t('Supplemental'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );   
   $form['tasks']['p1.3']['supplemental']['p1.3-TA_allow_edit+comment'] = array(
       '#type' => 'select',
       '#title' => t('Edit & comment?'),
       '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
       '#description' => t('Choose One'),
   );
   $form['tasks']['p1.3']['supplemental']['p1.3-TA_allow_comment_only'] = array(
       '#type' => 'select',
       '#title' => t('Comment only?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       '#description' => t('Choose One'),
	   );
   $form['tasks']['p1.3']['supplemental']['p1.3-TA_allow_revisions'] = array(
       '#type' => 'select',
       '#title' => t('Optionally send back for revisions?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p1.3']['supplemental']['p1.3-TA_allow_grade'] = array(
       '#type' => 'select',
       '#title' => t('Allow Grade?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p1.3']['supplemental']['p1.3-TA_number_participants'] = array(
       '#type' => 'hidden',
       '#title' => t('Number of participants'),
       '#options' => array(
         0 => t('1'),
         1 => t('2'),
         2 => t('3')
		 ),
		 '#default_value' =>0,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );	


   $form['tasks']['p1.3']['supplemental']['p1.3-TA_allow_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow Dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
   $form['tasks']['p1.3']['supplemental']['p1.3-TA_allow_resolve_grades'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve grades?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
   $form['tasks']['p1.3']['supplemental']['p1.3-TA_allow_resolve_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
	$form['tasks']['p1.3']['supplemental']['p1.3-TA_leads_to_new_problem'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
	$form['tasks']['p1.3']['supplemental']['p1.3-TA_leads_to_new_solution'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new solution'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);


// Automated

  $form['tasks']['p1.3']['TA_visual_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.3']['TA_Id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.3']['TA_WA_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.3']['TA_A_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.3']['TA_version_history'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.3']['TA_refers_to_which_task'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.3']['TA_trigger_condition'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.3']['TA_next_task'] = array(
	'#type' => 'hidden',
  );
 //************************************************************
 //	Create Problem ---- p1.4 ==> Grade
 //************************************************************
	$form['tasks']['p1.4'] = array(
    '#type' => 'fieldset',
    '#title' => t('Grade'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px">',
    '#suffix' => '</div>',
    '#states' => array(
		'visible' => array(
		':input[name="p1-TA_allow_grade"]' => array('value' => 1),
		),
	),
   ); 
    $form['tasks']['p1.4']['TA_type'] = array(
  	'#value' => 'grade',
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );

	$form['tasks']['p1.4']['use'] = array(
  	'#value' => TRUE,
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );
	$form['tasks']['p1.4']['basic'] = array(
    '#type' => 'fieldset',
    '#title' => t('Basic'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
  );   
  $form['tasks']['p1.4']['basic']['p1.4-TA_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Task Assignment Name:'),
    '#required' => TRUE,
    '#default_value' => "Grade",
    '#description' => "Please enter Task Assignment Name.",
    //'#size' => 20,
    //'#maxlength' => 20,
  );
$form['tasks']['p1.4']['basic']['p1.4-TA_due'] =array(
       '#type' => 'select',
       '#title' => t('When is task due?'),
       '#options' => array(
         0 => t('# of days after last task completes'),
         1 => t('Specific Date'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option 0 
   $form['tasks']['p1.4']['basic']['p1.4-TA_due select'] =array(
       '#type' => 'textfield',
       '#title' => 'Enter days:',
       '#default_value' => '3',
       '#states' => array(
			'visible' => array(
			':input[name="p1.4-TA_due"]' => array('value' => 0),
		),
	),
	);
   // option 1
   $form['tasks']['p1.4']['basic']['p1.4-TA_due date_select'] =array(
	'#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Specific Date:'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="p1.4-TA_due"]' => array('value' => 1),
		),
	),
	);

// Advanced

   $form['tasks']['p1.4']['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['p1.4']['advanced']['p1.4-TA_start_time'] =array(
     '#type' => 'select',
     '#title' => t('Start when prior task completes?'),
     '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
   //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option "NO"
   $form['tasks']['p1.4']['advanced']['p1.4-TA_start_time date_select'] =array(
	   '#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Start no earlier than specific date/time'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="p1.4-TA_start_time"]' => array('value' => 1),
		),
	),
   );
    $form['tasks']['p1.4']['advanced']['p1.4-TA_at_duration_end'] =array(
	   '#type' => 'select',
       '#title' => t('Task status at duration end'),
       '#options' => array(
         0 => t('Late'),
         1 => t('Complete'),
         2 => t('Resolved'),
		 ),
       '#default_value' => 0,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
    $form['tasks']['p1.4']['advanced']['p1.4-TA_what_if_late'] =array(
       '#type' => 'select',
       '#title' => t('What if late?'),
       '#options' => array(
         0 => t('Keep same participant'),
         1 => t('Allocate new participant'),
         2 => t('Allocate to instructor'),
         3 => t('Allocate to different group member'),
		 4 => t('Consider resolved'),
		 ),
       '#default_value' => 'Keep same participant',
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p1.4']['advanced']['p1.4-TA_display_name'] = array(
   	'#type' => 'textfield',
    	'#title' => t('Task Assignment Display Name:'),
    	'#required' => TRUE,
    	'#default_value' => "Create Problem",
    	'#description' => "Please enter Task Assignment Display Name.",	
   );
   $form['tasks']['p1.4']['advanced']['p1.4-TA_description'] = array(
   	'#type' => 'textarea',
    	'#title' => t('Description'),
    	'#required' => FALSE,
    	'#description' => "Please enter Description",	
   );
   $form['tasks']['p1.4']['advanced']['p1.4-TA_one_or_seperate'] = array(
       '#type' => 'hidden',
       '#title' => t('Everyone gets same problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	   '#default_value' => 0,
  );
   $form['tasks']['p1.4']['advanced']['p1.4-TA_assignee_constraints'] = array(
       '#type' => 'select',
       '#title' => t('Who does?'),
       '#options' => array(
         0 => t('Student'),
         1 => t('Instructor'),
		 ),  
	   '#default_value' => 0, 
  );

// option 0

   $form['tasks']['p1.4']['advanced']['p1.4-TA_assignee_constraints select'] = array(
        '#type' => 'radios',
        '#title' => 'Student does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="p1.4-TA_assignee_constraints"]' => array('value' => 0),
		),
	),
	);

   // option 1
   
   $form['tasks']['p1.4']['advanced']['p1.4-TA_assignee_constraints select1'] = array(
        '#type' => 'radios',
        '#title' => 'Instructor does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 1,
    	'#states' => array(
		'visible' => array(
		':input[name="p1.4-TA_assignee_constraints"]' => array('value' => 1),
		),
	),
	);
	
	$form['tasks']['p1.4']['advanced']['p1.4-TA_function_type'] = array(
       '#type' => 'select',
       '#title' => t('How should the final grade be determined?'),
       '#options' => array(
         0 => t('Max'),
         1 => t('Average'),
		 ),
  );

// template

   $form['tasks']['p1.4']['template'] = array(
    '#type' => 'fieldset',
    '#title' => t('Template'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['p1.4']['template']['p1.4-TA_instructions'] = array(
   	'#type' => 'textarea',
    '#title' => t('Instructions'),
    '#required' => TRUE,
    '#default_value' => '<p>Grade the solution to the specific problem shown '
	          .'above. (There are several different problems so be sure to read '
	          .'the one being solved here.) Each grade has several parts. Give '
	          .'a score and an explanation of that score for each part of the '
	          .'grade. Your explanation should be detailed, and several sentences '
	          .'long.</p>',
    '#description' => "Please enter instructions.",	
   );
   $form['tasks']['p1.4']['template']['p1.4-TA_rubric'] = array(
   	'#type' => 'textarea',
    '#title' => t('Rubric for grade'),
    '#required' => TRUE,
    '#default_value' => " ",
    	'#description' => "Please enter Rubric",	
   );


// Supplemental 

  $form['tasks']['p1.4']['supplemental'] = array(
    '#type' => 'fieldset',
    '#title' => t('Supplemental'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );   
   $form['tasks']['p1.4']['supplemental']['p1.4-TA_allow_edit+comment'] = array(
       '#type' => 'select',
       '#title' => t('Edit & comment?'),
       '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
       '#description' => t('Choose One'),
   );
   $form['tasks']['p1.4']['supplemental']['p1.4-TA_allow_comment_only'] = array(
       '#type' => 'select',
       '#title' => t('Comment only?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       '#description' => t('Choose One'),
	   );
   $form['tasks']['p1.4']['supplemental']['p1.4-TA_allow_revisions'] = array(
       '#type' => 'select',
       '#title' => t('Optionally send back for revisions?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p1.4']['supplemental']['p1.4-TA_allow_grade'] = array(
       '#type' => 'select',
       '#title' => t('Allow Grade?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p1.4']['supplemental']['p1.4-TA_number_participants'] = array(
       '#type' => 'hidden',
       '#title' => t('Number of participants'),
       '#options' => array(
         0 => t('1'),
         1 => t('2'),
         2 => t('3')
		 ),
		 '#default_value' =>1,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );	
	$form['tasks']['p1.4']['supplemental']['p1.4-TA_trigger_resolution_threshold'] = array(
       '#type' => 'select',
       '#title' => t('Grade resolution trigger conditions?'),
       '#options' => array(
         0 => t('Percent'),
         1 => t('Points'),
		 ),
	   '#states' => array(
			'visible' => array(
			':input[name="p1.4-TA_allow_grade"]' => array('value' => 1),
		),
		),
);
   // option "0"
   $form['tasks']['p1.4']['supplemental']['p1.4-TA_trigger_resolution_threshold option'] = array(
	'#type' => 'textfield',
    '#title' => t('Percent Amount'),
    '#default_value' => '15',
    '#states' => array(
		'visible' => array(
		':input[name="p1.4-TA_trigger_resolution_threshold"]' => array('value' => 0),
		':input[name="p1.4-TA_allow_grade"]' => array('value' => 1),
		),
	),
   );
   // option "1"
   $form['tasks']['p1.4']['supplemental']['p1.4-TA_trigger_resolution_threshold option1'] = array(
	'#type' => 'textfield',
    '#title' => t('Point Amount'),
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="p1.4-TA_trigger_resolution_threshold"]' => array('value' => 1),
		':input[name="p1.4-TA_allow_grade"]' => array('value' => 1),
		),
	),
   );

   $form['tasks']['p1.4']['supplemental']['p1.4-TA_allow_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow Dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
   $form['tasks']['p1.4']['supplemental']['p1.4-TA_allow_resolve_grades'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve grades?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
   $form['tasks']['p1.4']['supplemental']['p1.4-TA_allow_resolve_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
	$form['tasks']['p1.4']['supplemental']['p1.4-TA_leads_to_new_problem'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
	$form['tasks']['p1.4']['supplemental']['p1.4-TA_leads_to_new_solution'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new solution'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);


// Automated

  $form['tasks']['p1.4']['TA_visual_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.4']['TA_Id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.4']['TA_WA_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.4']['TA_A_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.4']['TA_version_history'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.4']['TA_refers_to_which_task'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.4']['TA_trigger_condition'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.4']['TA_next_task'] = array(
	'#type' => 'hidden',
  );



  //************************************************************
 //	Create Problem ---- p1.5 ==> Resolve Grades
 //************************************************************
 
 $form['tasks']['p1.5'] = array(
    '#type' => 'fieldset',
    '#title' => t('Resolve Grade'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px">',
    '#suffix' => '</div>',
    '#states' => array(
		'visible' => array(
		':input[name="p1-TA_allow_resolve_grades"]' => array('value' => 1),
		),
	),
   ); 
    $form['tasks']['p1.5']['TA_type'] = array(
  	'#value' => 'resolve grades',
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );

	$form['tasks']['p1.5']['use'] = array(
  	'#value' => TRUE,
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );
	$form['tasks']['p1.5']['basic'] = array(
    '#type' => 'fieldset',
    '#title' => t('Basic'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
  );   
  $form['tasks']['p1.5']['basic']['p1.5-TA_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Task Assignment Name:'),
    '#required' => TRUE,
    '#default_value' => "Resolve Grades",
    '#description' => "Please enter Task Assignment Name.",
    //'#size' => 20,
    //'#maxlength' => 20,
  );
  $form['tasks']['p1.5']['basic']['p1.5-TA_due'] =array(
       '#type' => 'select',
       '#title' => t('When is task due?'),
       '#options' => array(
         0 => t('# of days after last task completes'),
         1 => t('Specific Date'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option 0 
   $form['tasks']['p1.5']['basic']['p1.5-TA_due select'] =array(
       '#type' => 'textfield',
       '#title' => 'Enter days:',
       '#default_value' => '3',
       '#states' => array(
			'visible' => array(
			':input[name="p1.1-TA_due"]' => array('value' => 0),
		),
	),
	);
   // option 1
   $form['tasks']['p1.5']['basic']['p1.5-TA_due date_select'] =array(
	'#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Specific Date:'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="p1.5-TA_due"]' => array('value' => 1),
		),
	),
	);

// Advanced

   $form['tasks']['p1.5']['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['p1.5']['advanced']['p1.5-TA_start_time'] =array(
     '#type' => 'select',
     '#title' => t('Start when prior task completes?'),
     '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
   //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option "NO"
   $form['tasks']['p1.5']['advanced']['p1.5-TA_start_time date_select'] =array(
	   '#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Start no earlier than specific date/time'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="p1.1-TA_start_time"]' => array('value' => 1),
		),
	),
   );
    $form['tasks']['p1.5']['advanced']['p1.5-TA_at_duration_end'] =array(
	   '#type' => 'select',
       '#title' => t('Task status at duration end'),
       '#options' => array(
         0 => t('Late'),
         1 => t('Complete'),
         2 => t('Resolved'),
		 ),
       '#default_value' => 0,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
    $form['tasks']['p1.5']['advanced']['p1.5-TA_what_if_late'] =array(
       '#type' => 'select',
       '#title' => t('What if late?'),
       '#options' => array(
         0 => t('Keep same participant'),
         1 => t('Allocate new participant'),
         2 => t('Allocate to instructor'),
         3 => t('Allocate to different group member'),
	 4 => t('Consider resolved'),
		 ),
       '#default_value' => 'Keep same participant',
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p1.5']['advanced']['p1.5-TA_display_name'] = array(
   	'#type' => 'textfield',
    	'#title' => t('Task Assignment Display Name:'),
    	'#required' => TRUE,
    	'#default_value' => "Create Problem",
    	'#description' => "Please enter Task Assignment Display Name.",	
   );
   $form['tasks']['p1.5']['advanced']['p1.5-TA_description'] = array(
   	'#type' => 'textarea',
    	'#title' => t('Description'),
    	'#required' => FALSE,
    	'#description' => "Please enter Description",	
   );
   $form['tasks']['p1.5']['advanced']['p1.5-TA_one_or_seperate'] = array(
       '#type' => 'hidden',
       '#title' => t('Everyone gets same problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
  );
   $form['tasks']['p1.5']['advanced']['p1.5-TA_assignee_constraints'] = array(
       '#type' => 'select',
       '#title' => t('Who does?'),
       '#options' => array(
         0 => t('Student'),
         1 => t('Instructor'),
		 ),   
  );

// option 0

   $form['tasks']['p1.5']['advanced']['p1.5-TA_assignee_constraints select'] = array(
        '#type' => 'radios',
        '#title' => 'Student does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="p1.5-TA_assignee_constraints"]' => array('value' => 0),
		),
	),
	);

   // option 1
   
   $form['tasks']['p1.5']['advanced']['p1.5-TA_assignee_constraints select1'] = array(
        '#type' => 'radios',
        '#title' => 'Instructor does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="p1.5-TA_assignee_constraints"]' => array('value' => 1),
		),
	),
	);
   $form['tasks']['p1.5']['advanced']['p1.5-TA_function_type'] = array(
	
   );

// template

   $form['tasks']['p1.5']['template'] = array(
    '#type' => 'fieldset',
    '#title' => t('Template'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['p1.5']['template']['p1.5-TA_instructions'] = array(
   	'#type' => 'textarea',
    '#title' => t('Instructions'),
    '#required' => TRUE,
    '#default_value' => 'Because the regular graders did give the same '
          .'grade, please resolve the grade disagreement. Assign your '
          .'own score and justification for each part of the grade, and also '
          .'please provide an explanation.',
    '#description' => "Please enter instructions.",	
   );
   $form['tasks']['p1.5']['template']['p1.5-TA_rubric'] = array(
   	'#type' => 'textarea',
    '#title' => t('Rubric for edit & comment'),
    '#required' => TRUE,
    '#default_value' => " ",
    	'#description' => "Please enter Rubric",	
   );


// Supplemental 

  $form['tasks']['p1.5']['supplemental'] = array(
    '#type' => 'fieldset',
    '#title' => t('Supplemental'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );   
   $form['tasks']['p1.5']['supplemental']['p1.5-TA_allow_edit+comment'] = array(
       '#type' => 'select',
       '#title' => t('Edit & comment?'),
       '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
       '#description' => t('Choose One'),
   );
   $form['tasks']['p1.5']['supplemental']['p1.5-TA_allow_comment_only'] = array(
       '#type' => 'select',
       '#title' => t('Comment only?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       '#description' => t('Choose One'),
	   );
   $form['tasks']['p1.5']['supplemental']['p1.5-TA_allow_revisions'] = array(
       '#type' => 'select',
       '#title' => t('Optionally send back for revisions?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p1.5']['supplemental']['p1.5-TA_allow_grade'] = array(
       '#type' => 'select',
       '#title' => t('Allow Grade?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p1.5']['supplemental']['p1.5-TA_number_participants'] = array(
       '#type' => 'hidden',
       '#title' => t('Number of participants'),
       '#options' => array(
         0 => t('1'),
         1 => t('2'),
         2 => t('3')
		 ),
		 '#default_value' => 1,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );	

   $form['tasks']['p1.5']['supplemental']['p1.5-TA_allow_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow Dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
   $form['tasks']['p1.5']['supplemental']['p1.5-TA_allow_resolve_grades'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve grades?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
   $form['tasks']['p1.5']['supplemental']['p1.5-TA_allow_resolve_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
	$form['tasks']['p1.5']['supplemental']['p1.5-TA_leads_to_new_problem'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
	$form['tasks']['p1.5']['supplemental']['p1.5-TA_leads_to_new_solution'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new solution'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);


// Automated

  $form['tasks']['p1.5']['TA_visual_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.5']['TA_Id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.5']['TA_WA_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.5']['TA_A_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.5']['TA_version_history'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.5']['TA_refers_to_which_task'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.5']['TA_trigger_condition'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.5']['TA_next_task'] = array(
	'#type' => 'hidden',
  );


 //************************************************************
 //	Create Problem ---- p1.6 ==> Dispute
 //************************************************************
	$form['tasks']['p1.6'] = array(
    '#type' => 'fieldset',
    '#title' => t('Dispute'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px">',
    '#suffix' => '</div>',
    '#states' => array(
		'visible' => array(
		':input[name="p1-TA_allow_dispute"]' => array('value' => 1),
		),
	),
   ); 
    $form['tasks']['p1.6']['TA_type'] = array(
  	'#value' => 'dispute',
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );

	$form['tasks']['p1.6']['use'] = array(
  	'#value' => TRUE,
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );
	$form['tasks']['p1.6']['basic'] = array(
    '#type' => 'fieldset',
    '#title' => t('Basic'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
  );   
  $form['tasks']['p1.6']['basic']['p1.6-TA_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Task Assignment Name:'),
    '#required' => TRUE,
    '#default_value' => "Dispute",
    '#description' => "Please enter Task Assignment Name.",
    //'#size' => 20,
    //'#maxlength' => 20,
  );
  $form['tasks']['p1.6']['basic']['p1.6-TA_due'] =array(
       '#type' => 'select',
       '#title' => t('When is task due?'),
       '#options' => array(
         0 => t('# of days after last task completes'),
         1 => t('Specific Date'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option 0 
   $form['tasks']['p1.6']['basic']['p1.6-TA_due select'] =array(
       '#type' => 'textfield',
       '#title' => 'Enter days:',
       '#default_value' => '3',
       '#states' => array(
			'visible' => array(
			':input[name="p1.6-TA_due"]' => array('value' => 0),
		),
	),
	);
   // option 1
   $form['tasks']['p1.6']['basic']['p1.6-TA_due date_select'] =array(
	'#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Specific Date:'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="p1.6-TA_due"]' => array('value' => 1),
		),
	),
	);

// Advanced

   $form['tasks']['p1.6']['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['p1.6']['advanced']['p1.6-TA_start_time'] =array(
     '#type' => 'select',
     '#title' => t('Start when prior task completes?'),
     '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
   //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option "NO"
   $form['tasks']['p1.6']['advanced']['p1.6-TA_start_time date_select'] =array(
	   '#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Start no earlier than specific date/time'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="p1.6-TA_start_time"]' => array('value' => 1),
		),
	),
   );
    $form['tasks']['p1.6']['advanced']['p1.6-TA_at_duration_end'] =array(
	   '#type' => 'select',
       '#title' => t('Task status at duration end'),
       '#options' => array(
         0 => t('Late'),
         1 => t('Complete'),
         2 => t('Resolved'),
		 ),
       '#default_value' => 2,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
    $form['tasks']['p1.6']['advanced']['p1.6-TA_what_if_late'] =array(
       '#type' => 'select',
       '#title' => t('What if late?'),
       '#options' => array(
         0 => t('Keep same participant'),
         1 => t('Allocate new participant'),
         2 => t('Allocate to instructor'),
         3 => t('Allocate to different group member'),
	 4 => t('Consider resolved'),
		 ),
       '#default_value' => 4,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p1.6']['advanced']['p1.6-TA_display_name'] = array(
   	'#type' => 'textfield',
    	'#title' => t('Task Assignment Display Name:'),
    	'#required' => TRUE,
    	'#default_value' => "Create Problem",
    	'#description' => "Please enter Task Assignment Display Name.",	
   );
   $form['tasks']['p1.6']['advanced']['p1.6-TA_description'] = array(
   	'#type' => 'textarea',
    	'#title' => t('Description'),
    	'#required' => FALSE,
    	'#description' => "Please enter Description",	
   );
   $form['tasks']['p1.6']['advanced']['p1.6-TA_one_or_seperate'] = array(
       '#type' => 'hidden',
       '#title' => t('Everyone gets same problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
  );
  $form['tasks']['p1.6']['advanced']['p1.6-TA_assignee_constraints'] = array(
       '#type' => 'select',
       '#title' => t('Who does?'),
       '#options' => array(
         0 => t('Student'),
         1 => t('Instructor'),
		 ),   
  );

// option 0

   $form['tasks']['p1.6']['advanced']['p1.6-TA_assignee_constraints select'] = array(
        '#type' => 'radios',
        '#title' => 'Student does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="p1.6-TA_assignee_constraints"]' => array('value' => 0),
		),
	),
	);

   // option 1
   
   $form['tasks']['p1.6']['advanced']['p1.6-TA_assignee_constraints select1'] = array(
        '#type' => 'radios',
        '#title' => 'Instructor does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="p1.6-TA_assignee_constraints"]' => array('value' => 1),
		),
	),
	);

   $form['tasks']['p1.6']['advanced']['p1.6-TA_function_type'] = array(
	
   );

// template

   $form['tasks']['p1.6']['template'] = array(
    '#type' => 'fieldset',
    '#title' => t('Template'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['p1.6']['template']['p1.6-TA_instructions'] = array(
   	'#type' => 'textarea',
    '#title' => t('Instructions'),
    '#required' => TRUE,
    '#default_value' => 'You have the option to dispute your grade. To do '
          .'so, you need to fully grade your own solution. Assign your own '
          .'score and justification for each part of the grade. You must also '
          .'explain why the other graders were wrong.',
    '#description' => "Please enter instructions.",	
   );
   $form['tasks']['p1.6']['template']['p1.6-TA_rubric'] = array(
   	'#type' => 'textarea',
    '#title' => t('Rubric for edit & comment'),
    '#required' => TRUE,
    '#default_value' => " ",
    	'#description' => "Please enter Rubric",	
   );


// Supplemental 

  $form['tasks']['p1.6']['supplemental'] = array(
    '#type' => 'fieldset',
    '#title' => t('Supplemental'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );   
   $form['tasks']['p1.6']['supplemental']['p1.6-TA_allow_edit+comment'] = array(
       '#type' => 'select',
       '#title' => t('Edit & comment?'),
       '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
       '#description' => t('Choose One'),
   );
   $form['tasks']['p1.6']['supplemental']['p1.6-TA_allow_comment_only'] = array(
       '#type' => 'select',
       '#title' => t('Comment only?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       '#description' => t('Choose One'),
	   );
   $form['tasks']['p1.6']['supplemental']['p1.6-TA_allow_revisions'] = array(
       '#type' => 'select',
       '#title' => t('Optionally send back for revisions?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p1.6']['supplemental']['p1.6-TA_allow_grade'] = array(
       '#type' => 'select',
       '#title' => t('Allow Grade?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p1.6']['supplemental']['p1.6-TA_number_participants'] = array(
       '#type' => 'hidden',
       '#title' => t('Number of participants'),
       '#options' => array(
         0 => t('1'),
         1 => t('2'),
         2 => t('3')
		 ),
		 '#default_value' => 1,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );	


   $form['tasks']['p1.6']['supplemental']['p1.6-TA_allow_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow Dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
   $form['tasks']['p1.6']['supplemental']['p1.6-TA_allow_resolve_grades'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve grades?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
   $form['tasks']['p1.6']['supplemental']['p1.6-TA_allow_resolve_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
	'#default_value' => 1,
);
	$form['tasks']['p1.6']['supplemental']['p1.6-TA_leads_to_new_problem'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
	$form['tasks']['p1.6']['supplemental']['p1.6-TA_leads_to_new_solution'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new solution'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);


// Automated

  $form['tasks']['p1.6']['TA_visual_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.6']['TA_Id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.6']['TA_WA_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.6']['TA_A_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.6']['TA_version_history'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.6']['TA_refers_to_which_task'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.6']['TA_trigger_condition'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.6']['TA_next_task'] = array(
	'#type' => 'hidden',
  );


 //************************************************************
 //	Create Problem ---- p1.7 ==> Resolve Dispute
 //************************************************************
	$form['tasks']['p1.7'] = array(
    '#type' => 'fieldset',
    '#title' => t('Resolve Dispute'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px">',
    '#suffix' => '</div>',
    '#states' => array(
		'visible' => array(
		':input[name="p1-TA_allow_resolve_dispute"]' => array('value' => 1),
		),
	),
   ); 
    $form['tasks']['p1.7']['TA_type'] = array(
  	'#value' => 'resolve dispute',
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );

	$form['tasks']['p1.7']['use'] = array(
  	'#value' => TRUE,
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );
	$form['tasks']['p1.7']['basic'] = array(
    '#type' => 'fieldset',
    '#title' => t('Basic'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
  );   
 $form['tasks']['p1.7']['basic']['p1.7-TA_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Task Assignment Name:'),
    '#required' => TRUE,
    '#default_value' => "Resolve Dispute",
    '#description' => "Please enter Task Assignment Name.",
    //'#size' => 20,
    //'#maxlength' => 20,
  );
  $form['tasks']['p1.7']['basic']['p1.7-TA_due'] =array(
       '#type' => 'select',
       '#title' => t('When is task due?'),
       '#options' => array(
         0 => t('# of days after last task completes'),
         1 => t('Specific Date'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option 0 
   $form['tasks']['p1.7']['basic']['p1.7-TA_due select'] =array(
       '#type' => 'textfield',
       '#title' => 'Enter days:',
       '#default_value' => '3',
       '#states' => array(
			'visible' => array(
			':input[name="p1.7-TA_due"]' => array('value' => 0),
		),
	),
	);
   // option 1
   $form['tasks']['p1.7']['basic']['p1.7-TA_due date_select'] =array(
	'#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Specific Date:'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="p1.7-TA_due"]' => array('value' => 1),
		),
	),
	);

// Advanced

   $form['tasks']['p1.7']['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['p1.7']['advanced']['p1.7-TA_start_time'] =array(
     '#type' => 'select',
     '#title' => t('Start when prior task completes?'),
     '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
   //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option "NO"
   $form['tasks']['p1.7']['advanced']['p1.7-TA_start_time date_select'] =array(
	   '#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Start no earlier than specific date/time'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="p1.7-TA_start_time"]' => array('value' => 1),
		),
	),
   );
    $form['tasks']['p1.7']['advanced']['p1.7-TA_at_duration_end'] =array(
	   '#type' => 'select',
       '#title' => t('Task status at duration end'),
       '#options' => array(
         0 => t('Late'),
         1 => t('Complete'),
         2 => t('Resolved'),
		 ),
       '#default_value' => 0,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
    $form['tasks']['p1.7']['advanced']['p1.7-TA_what_if_late'] =array(
       '#type' => 'select',
       '#title' => t('What if late?'),
       '#options' => array(
         0 => t('Keep same participant'),
         1 => t('Allocate new participant'),
         2 => t('Allocate to instructor'),
         3 => t('Allocate to different group member'),
	 4 => t('Consider resolved'),
		 ),
       '#default_value' => 'Keep same participant',
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p1.7']['advanced']['p1.7-TA_display_name'] = array(
   	'#type' => 'textfield',
    	'#title' => t('Task Assignment Display Name:'),
    	'#required' => TRUE,
    	'#default_value' => "Create Problem",
    	'#description' => "Please enter Task Assignment Display Name.",	
   );
   $form['tasks']['p1.7']['advanced']['p1.7-TA_description'] = array(
   	'#type' => 'textarea',
    	'#title' => t('Description'),
    	'#required' => FALSE,
    	'#description' => "Please enter Description",	
   );
   $form['tasks']['p1.7']['advanced']['p1.7-TA_one_or_seperate'] = array(
       '#type' => 'hidden',
       '#title' => t('Everyone gets same problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
  );
   $form['tasks']['p1.7']['advanced']['p1.7-TA_assignee_constraints'] = array(
       '#type' => 'select',
       '#title' => t('Who does?'),
       '#options' => array(
         0 => t('Student'),
         1 => t('Instructor'),
		 ),
	'#default_value' => 1,   
  );

// option 0

   $form['tasks']['p1.7']['advanced']['p1.7-TA_assignee_constraints select'] = array(
        '#type' => 'radios',
        '#title' => 'Student does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="p1.7-TA_assignee_constraints"]' => array('value' => 0),
		),
	),
	);

   // option 1
   
   $form['tasks']['p1.7']['advanced']['p1.7-TA_assignee_constraints select1'] = array(
        '#type' => 'radios',
        '#title' => 'Instructor does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 1,
    	'#states' => array(
		'visible' => array(
		':input[name="p1.7-TA_assignee_constraints"]' => array('value' => 1),
		),
	),
	);
   $form['tasks']['p1.7']['advanced']['p1.7-TA_function_type'] = array(
	
   );

// template

   $form['tasks']['p1.7']['template'] = array(
    '#type' => 'fieldset',
    '#title' => t('Template'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['p1.7']['template']['p1.7-TA_instructions'] = array(
   	'#type' => 'textarea',
    '#title' => t('Instructions'),
    '#required' => TRUE,
    '#default_value' => 'The problem solver is disputing his or her grade. '
          .'You need to provide the final grade. Assign a final score with '
          .'justification for each part of the grade, and also please provide '
          .'an explanation.',
    '#description' => "Please enter instructions.",	
   );
   $form['tasks']['p1.7']['template']['p1.7-TA_rubric'] = array(
   	'#type' => 'textarea',
    '#title' => t('Rubric for edit & comment'),
    '#required' => TRUE,
    '#default_value' => " ",
    	'#description' => "Please enter Rubric",	
   );


// Supplemental 

  $form['tasks']['p1.7']['supplemental'] = array(
    '#type' => 'fieldset',
    '#title' => t('Supplemental'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );   
   $form['tasks']['p1.7']['supplemental']['p1.7-TA_allow_edit+comment'] = array(
       '#type' => 'select',
       '#title' => t('Edit & comment?'),
       '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
       '#description' => t('Choose One'),
   );
   $form['tasks']['p1.7']['supplemental']['p1.7-TA_allow_comment_only'] = array(
       '#type' => 'select',
       '#title' => t('Comment only?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       '#description' => t('Choose One'),
	   );
   $form['tasks']['p1.7']['supplemental']['p1.7-TA_allow_revisions'] = array(
       '#type' => 'select',
       '#title' => t('Optionally send back for revisions?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p1.7']['supplemental']['p1.7-TA_allow_grade'] = array(
       '#type' => 'select',
       '#title' => t('Allow Grade?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['p1.7']['supplemental']['p1.7-TA_number_participants'] = array(
       '#type' => 'hidden',
       '#title' => t('Number of participants'),
       '#options' => array(
         0 => t('1'),
         1 => t('2'),
         2 => t('3')
		 ),
		 '#default_value' => 1,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );	

   $form['tasks']['p1.7']['supplemental']['p1.7-TA_allow_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow Dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
   $form['tasks']['p1.7']['supplemental']['p1.7-TA_allow_resolve_grades'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve grades?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
   $form['tasks']['p1.7']['supplemental']['p1.7-TA_allow_resolve_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
	$form['tasks']['p1.7']['supplemental']['p1.7-TA_leads_to_new_problem'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
	$form['tasks']['p1.7']['supplemental']['p1.7-TA_leads_to_new_solution'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new solution'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);


// Automated

  $form['tasks']['p1.7']['TA_visual_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.7']['TA_Id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.7']['TA_WA_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.7']['TA_A_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.7']['TA_version_history'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.7']['TA_refers_to_which_task'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.7']['TA_trigger_condition'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['p1.7']['TA_next_task'] = array(
	'#type' => 'hidden',
  );


  //*******************************************
  // Solve Problem ---- s1 ==> Solve Problem
  //*******************************************
  $form['tasks']['s1'] = array(
    '#type' => 'fieldset',
    '#title' => t('Solve Problem'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px">',
    '#suffix' => '</div>',
    );
  $form['tasks']['s1']['TA_type'] = array(
    '#value' => 'create problem',
    '#type' => 'hidden',
    '#collapsible' => FALSE,
  );

// Basic

  $form['tasks']['s1']['basic'] = array(
    '#type' => 'fieldset',
    '#title' => t('Basic'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
  );   
  $form['tasks']['s1']['basic']['s1-TA_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Task Assignment Name:'),
    '#required' => TRUE,
    '#default_value' => "Create Problem",
    '#description' => "Please enter Task Assignment Name.",
    //'#size' => 20,
    //'#maxlength' => 20,
  );
  $form['tasks']['s1']['basic']['s1-A_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Assignment Name:'),
    '#required' => TRUE,
    '#default_value' => "Assignment 1",
    '#description' => "Please enter Assignment Name.",
    //'#size' => 20,
    //'#maxlength' => 20,
  );
  $form['tasks']['s1']['basic']['s1-A_type'] = array(
       '#type' => 'select',
       '#title' => t('type of assignment?'),
       '#options' => array(
         0 => t('Exam'),
         1 => t('Homework'),
         2 => t('Quiz'),
         3 => t('Lab'),
		 ),
       '#default_value' => 'Homework',
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
  $form['tasks']['s1']['basic']['s1-TA_due'] =array(
	   '#type' => 'select',
       '#title' => t('When is task due?'),
       '#options' => array(
         0 => t('# of days after last task completes'),
         1 => t('Specific Date'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option 0 
   $form['tasks']['s1']['basic']['s1-TA_due select'] =array(
       '#type' => 'textfield',
       '#title' => 'Enter days:',
       '#default_value' => '3',
       '#states' => array(
			'visible' => array(
			':input[name="s1-TA_due"]' => array('value' => 0),
		),
	),
	);
   // option 1
   $form['tasks']['s1']['basic']['s1-TA_due date_select'] =array(
	'#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Specific Date:'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="s1-TA_due"]' => array('value' => 1),
		),
	),
	);

// Advanced

   $form['tasks']['s1']['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['s1']['advanced']['s1-TA_start_time'] =array(
     '#type' => 'select',
     '#title' => t('Start when prior task completes?'),
     '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
   //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option "NO"
   $form['tasks']['s1']['advanced']['s1-TA_start_time date_select'] =array(
	   '#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Start no earlier than specific date/time'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="s1-TA_start_time"]' => array('value' => 1),
		),
	),
   );
    $form['tasks']['s1']['advanced']['s1-TA_at_duration_end'] =array(
	   '#type' => 'select',
       '#title' => t('Task status at duration end'),
       '#options' => array(
         0 => t('Late'),
         1 => t('Complete'),
         2 => t('Resolved'),
		 ),
       '#default_value' => 0,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
    $form['tasks']['s1']['advanced']['s1-TA_what_if_late'] =array(
	   '#type' => 'select',
       '#title' => t('What if late?'),
       '#options' => array(
         0 => t('Keep same participant'),
         1 => t('Allocate new participant'),
         2 => t('Allocate to instructor'),
         3 => t('Allocate to different group member'),
	 4 => t('Consider resolved'),
		 ),
       '#default_value' => 'Keep same participant',
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1']['advanced']['s1-TA_display_name'] = array(
   	'#type' => 'textfield',
    	'#title' => t('Task Assignment Display Name:'),
    	'#required' => TRUE,
    	'#default_value' => "Create Problem",
    	'#description' => "Please enter Task Assignment Display Name.",	
   );
   $form['tasks']['s1']['advanced']['s1-TA_description'] = array(
   		'#type' => 'textarea',
    	'#title' => t('Description'),
    	'#required' => FALSE,
    	'#description' => "Please enter Description",	
   );
   $form['tasks']['s1']['advanced']['s1-TA_one_or_seperate'] = array(
       '#type' => 'select',
       '#title' => t('Everyone gets same problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
  );
   $form['tasks']['s1']['advanced']['s1-TA_assignee_constraints'] = array(
       '#type' => 'select',
       '#title' => t('Who does?'),
       '#options' => array(
         0 => t('Student'),
         1 => t('Instructor'),
		 ),   
  );

// option 0

   $form['tasks']['s1']['advanced']['s1-TA_assignee_constraints select'] = array(
        '#type' => 'radios',
        '#title' => 'Student does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="s1-TA_assignee_constraints"]' => array('value' => 0),
		),
	),
	);

   // option 1
   
   $form['tasks']['s1']['advanced']['s1-TA_assignee_constraints select1'] = array(
        '#type' => 'radios',
        '#title' => 'Instructor does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="s1-TA_assignee_constraints"]' => array('value' => 1),
		),
	),
	);
   $form['tasks']['s1']['advanced']['s1-TA_function_type'] = array(
	
   );

// template

   $form['tasks']['s1']['template'] = array(
    '#type' => 'fieldset',
    '#title' => t('Template'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['s1']['template']['s1-TA_instructions'] = array(
   	'#type' => 'textarea',
    '#title' => t('Instructions'),
    '#required' => TRUE,
    '#default_value' => 'Read the assignment instructions and enter '
          .'a problem in the box below. Make your problem as clear as '
          .'possible so the person solving it will understand what you mean. '
          .'This solution is graded out of 100 points.',
    '#description' => "Please enter instructions.",	
   );
   $form['tasks']['s1']['template']['s1-TA_rubric'] = array(
   	'#type' => 'textarea',
    '#title' => t('Rubric'),
    '#required' => TRUE,
    '#default_value' => " ",
    	'#description' => "Please enter Rubric",	
   );


// Supplemental 

  $form['tasks']['s1']['supplemental'] = array(
    '#type' => 'fieldset',
    '#title' => t('Supplemental'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );   
   $form['tasks']['s1']['supplemental']['s1-TA_allow_edit+comment'] = array(
       '#type' => 'select',
       '#title' => t('Edit & comment?'),
       '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
       '#description' => t('Choose One'),
   );
   $form['tasks']['s1']['supplemental']['s1-TA_allow_comment_only'] = array(
       '#type' => 'select',
       '#title' => t('Comment only?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       '#description' => t('Choose One'),
	   );
   $form['tasks']['s1']['supplemental']['s1-TA_allow_revisions'] = array(
       '#type' => 'select',
       '#title' => t('Optionally send back for revisions?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1']['supplemental']['s1-TA_allow_grade'] = array(
       '#type' => 'select',
       '#title' => t('Allow Grade?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	'#default_value' => 1,
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1']['supplemental']['s1-TA_number_participants'] = array(
       '#type' => 'hidden',
       '#title' => t('Number of participants'),
       '#options' => array(
         0 => t('1'),
         1 => t('2'),
         2 => t('3')
		 ),
		 '#default_value' => 1,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );	

   $form['tasks']['s1']['supplemental']['s1-TA_allow_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow Dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
   $form['tasks']['s1']['supplemental']['s1-TA_allow_resolve_grades'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve grades?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
   $form['tasks']['s1']['supplemental']['s1-TA_allow_resolve_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
	$form['tasks']['s1']['supplemental']['s1-TA_leads_to_new_problem'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
	$form['tasks']['s1']['supplemental']['s1-TA_leads_to_new_solution'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new solution'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);


// Automated

  $form['tasks']['s1']['TA_visual_id'] = array(

  );

  $form['tasks']['s1']['TA_Id'] = array(

  );

  $form['tasks']['s1']['TA_WA_id'] = array(

  );

  $form['tasks']['s1']['TA_A_id'] = array(

  );

  $form['tasks']['s1']['TA_version_history'] = array(

  );

  $form['tasks']['s1']['TA_refers_to_which_task'] = array(

  );

  $form['tasks']['s1']['TA_trigger_condition'] = array(

  );

  $form['tasks']['s1']['TA_next_task'] = array(

  );
   
//************************************************************
//	Solve Problem ---- s1.1 ==> Edit & Comment
//************************************************************


   	$form['tasks']['s1.1'] = array(
    '#type' => 'fieldset',
    '#title' => t('Edit & Comment'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px">',
    '#suffix' => '</div>',
    '#states' => array(
		'visible' => array(
		':input[name="s1-TA_allow_edit+comment"]' => array('value' => 1),
		),
	),
   );	  
   
   $form['tasks']['s1.1']['TA_type'] = array(
  	'#value' => 'edit & comment',
    	'#type' => 'hidden',
    	'#collapsible' => FALSE,
    );
   $form['tasks']['s1.1']['use'] = array(
    '#value' => TRUE,
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );
	$form['tasks']['s1.1']['basic'] = array(
    '#type' => 'fieldset',
    '#title' => t('Basic'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
  );   
  $form['tasks']['s1.1']['basic']['s1.1-TA_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Task Assignment Name:'),
    '#required' => TRUE,
    '#default_value' => "Edit & Comment",
    '#description' => "Please enter Task Assignment Name.",
    //'#size' => 20,
    //'#maxlength' => 20,
  );
  $form['tasks']['s1.1']['basic']['s1.1-TA_due'] =array(
       '#type' => 'select',
       '#title' => t('When is task due?'),
       '#options' => array(
         0 => t('# of days after last task completes'),
         1 => t('Specific Date'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option 0 
   $form['tasks']['s1.1']['basic']['s1.1-TA_due select'] =array(
       '#type' => 'textfield',
       '#title' => 'Enter days:',
       '#default_value' => '3',
       '#states' => array(
			'visible' => array(
			':input[name="s1.1-TA_due"]' => array('value' => 0),
		),
	),
	);
   // option 1
   $form['tasks']['s1.1']['basic']['s1.1-TA_due date_select'] =array(
	'#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Specific Date:'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="s1.1-TA_due"]' => array('value' => 1),
		),
	),
	);

// Advanced

   $form['tasks']['s1.1']['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['s1.1']['advanced']['s1.1-TA_start_time'] =array(
     '#type' => 'select',
     '#title' => t('Start when prior task completes?'),
     '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
   //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option "NO"
   $form['tasks']['s1.1']['advanced']['s1.1-TA_start_time date_select'] =array(
	   '#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Start no earlier than specific date/time'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="s1.1-TA_start_time"]' => array('value' => 1),
		),
	),
   );
    $form['tasks']['s1.1']['advanced']['s1.1-TA_at_duration_end'] =array(
	   '#type' => 'select',
       '#title' => t('Task status at duration end'),
       '#options' => array(
         0 => t('Late'),
         1 => t('Complete'),
         2 => t('Resolved'),
		 ),
       '#default_value' => 0,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
    $form['tasks']['s1.1']['advanced']['s1.1-TA_what_if_late'] =array(
       '#type' => 'select',
       '#title' => t('What if late?'),
       '#options' => array(
         0 => t('Keep same participant'),
         1 => t('Allocate new participant'),
         2 => t('Allocate to instructor'),
         3 => t('Allocate to different group member'),
	 4 => t('Consider resolved'),
		 ),
       '#default_value' => 'Keep same participant',
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1.1']['advanced']['s1.1-TA_display_name'] = array(
   	'#type' => 'textfield',
    	'#title' => t('Task Assignment Display Name:'),
    	'#required' => TRUE,
    	'#default_value' => "Create Problem",
    	'#description' => "Please enter Task Assignment Display Name.",	
   );
   $form['tasks']['s1.1']['advanced']['s1.1-TA_description'] = array(
   	'#type' => 'textarea',
    	'#title' => t('Description'),
    	'#required' => FALSE,
    	'#description' => "Please enter Description",	
   );
   $form['tasks']['s1.1']['advanced']['s1.1-TA_one_or_seperate'] = array(
       '#type' => 'hidden',
       '#title' => t('Everyone gets same problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
  );
   $form['tasks']['s1.1']['advanced']['s1.1-TA_assignee_constraints'] = array(
       '#type' => 'select',
       '#title' => t('Who does?'),
       '#options' => array(
         0 => t('Student'),
         1 => t('Instructor'),
		 ),   
  );

// option 0

   $form['tasks']['s1.1']['advanced']['s1.1-TA_assignee_constraints select'] = array(
        '#type' => 'radios',
        '#title' => 'Student does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="s1.1-TA_assignee_constraints"]' => array('value' => 0),
		),
	),
	);

   // option 1
   
   $form['tasks']['s1.1']['advanced']['s1.1-TA_assignee_constraints select1'] = array(
        '#type' => 'radios',
        '#title' => 'Instructor does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="s1.1-TA_assignee_constraints"]' => array('value' => 1),
		),
	),
	);
   $form['tasks']['s1.1']['advanced']['s1.1-TA_function_type'] = array(
	
   );

// template

   $form['tasks']['s1.1']['template'] = array(
    '#type' => 'fieldset',
    '#title' => t('Template'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['s1.1']['template']['s1.1-TA_instructions'] = array(
   	'#type' => 'textarea',
    '#title' => t('Instructions'),
    '#required' => TRUE,
    '#default_value' => 'Rephrase the problem (if necessary) so it is '
          .'appropriate to the assignment and clear to the person solving '
          .'it. The solver and graders will only see your edited version, not '
          .'the original version. (Others not involved in solving or grading '
          .'will see both the original and edited versions.) You can also '
          .'leave a comment to explain any rephrasing.',
    '#description' => "Please enter instructions.",	
   );
   $form['tasks']['s1.1']['template']['s1.1-TA_rubric'] = array(
   	'#type' => 'textarea',
    '#title' => t('Rubric for edit & comment'),
    '#required' => TRUE,
    '#default_value' => " ",
    	'#description' => "Please enter Rubric",	
   );


// Supplemental 

  $form['tasks']['s1.1']['supplemental'] = array(
    '#type' => 'fieldset',
    '#title' => t('Supplemental'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );   
   $form['tasks']['s1.1']['supplemental']['s1.1-TA_allow_edit+comment'] = array(
       '#type' => 'select',
       '#title' => t('Edit & comment?'),
       '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
       '#description' => t('Choose One'),
   );
   $form['tasks']['s1.1']['supplemental']['s1.1-TA_allow_comment_only'] = array(
       '#type' => 'select',
       '#title' => t('Comment only?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       '#description' => t('Choose One'),
	   );
   $form['tasks']['s1.1']['supplemental']['s1.1-TA_allow_revisions'] = array(
       '#type' => 'select',
       '#title' => t('Optionally send back for revisions?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1.1']['supplemental']['s1.1-TA_allow_grade'] = array(
       '#type' => 'select',
       '#title' => t('Allow Grade?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1.1']['supplemental']['s1.1-TA_number_participants'] = array(
       '#type' => 'hidden',
       '#title' => t('Number of participants'),
       '#options' => array(
         0 => t('1'),
         1 => t('2'),
         2 => t('3')
		 ),
		 '#default_value' => 1,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );	


   $form['tasks']['s1.1']['supplemental']['s1.1-TA_allow_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow Dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
   $form['tasks']['s1.1']['supplemental']['s1.1-TA_allow_resolve_grades'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve grades?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
   $form['tasks']['s1.1']['supplemental']['s1.1-TA_allow_resolve_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
	$form['tasks']['s1.1']['supplemental']['s1.1-TA_leads_to_new_problem'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
	$form['tasks']['s1.1']['supplemental']['s1.1-TA_leads_to_new_solution'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new solution'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);


// Automated

  $form['tasks']['s1.1']['TA_visual_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.1']['TA_Id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.1']['TA_WA_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.1']['TA_A_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.1']['TA_version_history'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.1']['TA_refers_to_which_task'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.1']['TA_trigger_condition'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.1']['TA_next_task'] = array(
	'#type' => 'hidden',
  );


 //************************************************************
 //	Create Problem ---- p1.2 ==> Comment Only 
 //************************************************************

	$form['tasks']['s1.2'] = array(
    '#type' => 'fieldset',
    '#title' => t('Comment Only'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px">',
    '#suffix' => '</div>',
    '#states' => array(
		'visible' => array(
		':input[name="s1-TA_allow_comment_only"]' => array('value' => 1),
		),
	),
   ); 
    $form['tasks']['s1.2']['TA_type'] = array(
  	'#value' => 'comment only',
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );
	
	$form['tasks']['s1.2']['use'] = array(
  	'#value' => TRUE,
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );
	$form['tasks']['s1.2']['basic'] = array(
    '#type' => 'fieldset',
    '#title' => t('Basic'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
  );   
  $form['tasks']['s1.2']['basic']['s1.2-TA_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Task Assignment Name:'),
    '#required' => TRUE,
    '#default_value' => "Comment Only",
    '#description' => "Please enter Task Assignment Name.",
    //'#size' => 20,
    //'#maxlength' => 20,
  );
  $form['tasks']['s1.2']['basic']['s1.2-TA_due'] =array(
       '#type' => 'select',
       '#title' => t('When is task due?'),
       '#options' => array(
         0 => t('# of days after last task completes'),
         1 => t('Specific Date'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option 0 
   $form['tasks']['s1.2']['basic']['s1.2-TA_due select'] =array(
       '#type' => 'textfield',
       '#title' => 'Enter days:',
       '#default_value' => '3',
       '#states' => array(
			'visible' => array(
			':input[name="s1.2-TA_due"]' => array('value' => 0),
		),
	),
	);
   // option 1
   $form['tasks']['s1.2']['basic']['s1.2-TA_due date_select'] =array(
	'#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Specific Date:'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="s1.2-TA_due"]' => array('value' => 1),
		),
	),
	);

// Advanced

   $form['tasks']['s1.2']['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['s1.2']['advanced']['s1.2-TA_start_time'] =array(
     '#type' => 'select',
     '#title' => t('Start when prior task completes?'),
     '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
   //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option "NO"
   $form['tasks']['s1.2']['advanced']['s1.2-TA_start_time date_select'] =array(
	   '#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Start no earlier than specific date/time'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="s1.2-TA_start_time"]' => array('value' => 1),
		),
	),
   );
    $form['tasks']['s1.2']['advanced']['s1.2-TA_at_duration_end'] =array(
	   '#type' => 'select',
       '#title' => t('Task status at duration end'),
       '#options' => array(
         0 => t('Late'),
         1 => t('Complete'),
         2 => t('Resolved'),
		 ),
       '#default_value' => 0,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
    $form['tasks']['s1.2']['advanced']['s1.2-TA_what_if_late'] =array(
       '#type' => 'select',
       '#title' => t('What if late?'),
       '#options' => array(
         0 => t('Keep same participant'),
         1 => t('Allocate new participant'),
         2 => t('Allocate to instructor'),
         3 => t('Allocate to different group member'),
		 4 => t('Consider resolved'),
		 ),
       '#default_value' => 'Keep same participant',
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1.2']['advanced']['s1.2-TA_display_name'] = array(
   	'#type' => 'textfield',
    	'#title' => t('Task Assignment Display Name:'),
    	'#required' => TRUE,
    	'#default_value' => "Create Problem",
    	'#description' => "Please enter Task Assignment Display Name.",	
   );
   $form['tasks']['s1.2']['advanced']['s1.2-TA_description'] = array(
   	'#type' => 'textarea',
    	'#title' => t('Description'),
    	'#required' => FALSE,
    	'#description' => "Please enter Description",	
   );
   $form['tasks']['s1.2']['advanced']['s1.2-TA_one_or_seperate'] = array(
       '#type' => 'hidden',
       '#title' => t('Everyone gets same problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	   '#default_value' => 0,
  );
   $form['tasks']['s1.2']['advanced']['s1.2-TA_assignee_constraints'] = array(
       '#type' => 'select',
       '#title' => t('Who does?'),
       '#options' => array(
         0 => t('Student'),
         1 => t('Instructor'),
		 ),  
	   '#default_value' => 1, 
  );

// option 0

   $form['tasks']['s1.2']['advanced']['s1.2-TA_assignee_constraints-select'] = array(
        '#type' => 'radios',
        '#title' => 'Student does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="s1.2-TA_assignee_constraints"]' => array('value' => 0),
		),
	),
	);

   // option 1
   
   $form['tasks']['s1.2']['advanced']['s1.2-TA_assignee_constraints-select1'] = array(
        '#type' => 'radios',
        '#title' => 'Instructor does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 1,
    	'#states' => array(
		'visible' => array(
		':input[name="s1.2-TA_assignee_constraints"]' => array('value' => 1),
		),
	),
	);
   $form['tasks']['s1.2']['advanced']['s1.2-TA_function_type'] = array(
	
   );

// template

   $form['tasks']['s1.2']['template'] = array(
    '#type' => 'fieldset',
    '#title' => t('Template'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['s1.2']['template']['s1.2-TA_instructions'] = array(
   	'#type' => 'textarea',
    '#title' => t('Instructions'),
    '#required' => TRUE,
    '#default_value' => 'TBD',
    '#description' => "Please enter instructions.",	
   );
   $form['tasks']['s1.2']['template']['s1.2-TA_rubric'] = array(
   	'#type' => 'textarea',
    '#title' => t('Rubric for comment only'),
    '#required' => TRUE,
    '#default_value' => " ",
    	'#description' => "Please enter Rubric",	
   );


// Supplemental 

  $form['tasks']['s1.2']['supplemental'] = array(
    '#type' => 'fieldset',
    '#title' => t('Supplemental'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );   
   $form['tasks']['s1.2']['supplemental']['s1.2-TA_allow_edit+comment'] = array(
       '#type' => 'select',
       '#title' => t('Edit & comment?'),
       '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
       '#description' => t('Choose One'),
   );
   $form['tasks']['s1.2']['supplemental']['s1.2-TA_allow_comment_only'] = array(
       '#type' => 'select',
       '#title' => t('Comment only?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       '#description' => t('Choose One'),
	   );
   $form['tasks']['s1.2']['supplemental']['s1.2-TA_allow_revisions'] = array(
       '#type' => 'select',
       '#title' => t('Optionally send back for revisions?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1.2']['supplemental']['s1.2-TA_allow_grade'] = array(
       '#type' => 'select',
       '#title' => t('Allow Grade?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1.2']['supplemental']['s1.2-TA_number_participants'] = array(
       '#type' => 'hidden',
       '#title' => t('Number of participants'),
       '#options' => array(
         0 => t('1'),
         1 => t('2'),
         2 => t('3')
		 ),
		 '#default_value' =>0,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );	


   $form['tasks']['s1.2']['supplemental']['s1.2-TA_allow_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow Dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
   $form['tasks']['s1.2']['supplemental']['s1.2-TA_allow_resolve_grades'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve grades?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
   $form['tasks']['s1.2']['supplemental']['s1.2-TA_allow_resolve_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
	$form['tasks']['s1.2']['supplemental']['s1.2-TA_leads_to_new_problem'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
	$form['tasks']['s1.2']['supplemental']['s1.2-TA_leads_to_new_solution'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new solution'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);


// Automated

  $form['tasks']['s1.2']['TA_visual_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.2']['TA_Id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.2']['TA_WA_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.2']['TA_A_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.2']['TA_version_history'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.2']['TA_refers_to_which_task'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.2']['TA_trigger_condition'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.2']['TA_next_task'] = array(
	'#type' => 'hidden',
  );


 //************************************************************
 //	Solve Problem ---- s1.3 ==> Revise & Resubmit
 //************************************************************
 
	$form['tasks']['s1.3'] = array(
    '#type' => 'fieldset',
    '#title' => t('Revise & Resubmit'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px">',
    '#suffix' => '</div>',
    '#states' => array(
		'visible' => array(
		':input[name="s1-TA_allow_revisions"]' => array('value' => 1),
		),
	),
   ); 
    $form['tasks']['s1.3']['TA_type'] = array(
  	'#value' => 'revise & resubmit',
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );

	$form['tasks']['s1.3']['use'] = array(
  	'#value' => TRUE,
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );
	$form['tasks']['s1.3']['basic'] = array(
    '#type' => 'fieldset',
    '#title' => t('Basic'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
  );   
  $form['tasks']['s1.3']['basic']['s1.3-TA_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Task Assignment Name:'),
    '#required' => TRUE,
    '#default_value' => "Revise & Resubmit",
    '#description' => "Please enter Task Assignment Name.",
    //'#size' => 20,
    //'#maxlength' => 20,
  );
 $form['tasks']['s1.3']['basic']['s1.3-TA_due'] =array(
       '#type' => 'select',
       '#title' => t('When is task due?'),
       '#options' => array(
         0 => t('# of days after last task completes'),
         1 => t('Specific Date'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option 0 
   $form['tasks']['s1.3']['basic']['s1.3-TA_due select'] =array(
       '#type' => 'textfield',
       '#title' => 'Enter days:',
       '#default_value' => '3',
       '#states' => array(
			'visible' => array(
			':input[name="s1.3-TA_due"]' => array('value' => 0),
		),
	),
	);
   // option 1
   $form['tasks']['s1.3']['basic']['s1.3-TA_due date_select'] =array(
	'#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Specific Date:'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="s1.3-TA_due"]' => array('value' => 1),
		),
	),
	);

// Advanced

   $form['tasks']['s1.3']['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['s1.3']['advanced']['s1.3-TA_start_time'] =array(
     '#type' => 'select',
     '#title' => t('Start when prior task completes?'),
     '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
   //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option "NO"
   $form['tasks']['s1.3']['advanced']['s1.3-TA_start_time date_select'] =array(
	   '#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Start no earlier than specific date/time'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="p1.3-TA_start_time"]' => array('value' => 1),
		),
	),
   );
    $form['tasks']['s1.3']['advanced']['s1.3-TA_at_duration_end'] =array(
	   '#type' => 'select',
       '#title' => t('Task status at duration end'),
       '#options' => array(
         0 => t('Late'),
         1 => t('Complete'),
         2 => t('Resolved'),
		 ),
       '#default_value' => 1,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
    $form['tasks']['s1.3']['advanced']['s1.3-TA_what_if_late'] =array(
       '#type' => 'select',
       '#title' => t('What if late?'),
       '#options' => array(
         0 => t('Keep same participant'),
         1 => t('Allocate new participant'),
         2 => t('Allocate to instructor'),
         3 => t('Allocate to different group member'),
		 4 => t('Consider resolved'),
		 ),
       '#default_value' => 4,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1.3']['advanced']['s1.3-TA_display_name'] = array(
   	'#type' => 'textfield',
    	'#title' => t('Task Assignment Display Name:'),
    	'#required' => TRUE,
    	'#default_value' => "Create Problem",
    	'#description' => "Please enter Task Assignment Display Name.",	
   );
   $form['tasks']['s1.3']['advanced']['s1.3-TA_description'] = array(
   	'#type' => 'textarea',
    	'#title' => t('Description'),
    	'#required' => FALSE,
    	'#description' => "Please enter Description",	
   );
   $form['tasks']['s1.3']['advanced']['s1.3-TA_one_or_seperate'] = array(
       '#type' => 'hidden',
       '#title' => t('Everyone gets same problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	   '#default_value' => 0,
  );
   $form['tasks']['s1.3']['advanced']['s1.3-TA_assignee_constraints'] = array(
       '#type' => 'select',
       '#title' => t('Who does?'),
       '#options' => array(
         0 => t('Student'),
         1 => t('Instructor'),
		 ),  
	   '#default_value' => 0, 
  );

// option 0

   $form['tasks']['s1.3']['advanced']['s1.3-TA_assignee_constraints select'] = array(
        '#type' => 'radios',
        '#title' => 'Student does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="s1.3-TA_assignee_constraints"]' => array('value' => 0),
		),
	),
	);

   // option 1
   
   $form['tasks']['s1.3']['advanced']['s1.3-TA_assignee_constraints select1'] = array(
        '#type' => 'radios',
        '#title' => 'Instructor does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 1,
    	'#states' => array(
		'visible' => array(
		':input[name="s1.3-TA_assignee_constraints"]' => array('value' => 1),
		),
	),
	);
   $form['tasks']['s1.3']['advanced']['s1.3-TA_function_type'] = array(
	
   );

// template

   $form['tasks']['s1.3']['template'] = array(
    '#type' => 'fieldset',
    '#title' => t('Template'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['p1.3']['template']['p1.3-TA_instructions'] = array(
   	'#type' => 'textarea',
    '#title' => t('Instructions'),
    '#required' => TRUE,
    '#default_value' => 'TBD',
    '#description' => "Please enter instructions.",	
   );
   $form['tasks']['s1.3']['template']['s1.3-TA_rubric'] = array(
   	'#type' => 'textarea',
    '#title' => t('Rubric for revise & resubmit'),
    '#required' => TRUE,
    '#default_value' => " ",
    	'#description' => "Please enter Rubric",	
   );


// Supplemental 

  $form['tasks']['s1.3']['supplemental'] = array(
    '#type' => 'fieldset',
    '#title' => t('Supplemental'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );   
   $form['tasks']['s1.3']['supplemental']['s1.3-TA_allow_edit+comment'] = array(
       '#type' => 'select',
       '#title' => t('Edit & comment?'),
       '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
       '#description' => t('Choose One'),
   );
   $form['tasks']['s1.3']['supplemental']['s1.3-TA_allow_comment_only'] = array(
       '#type' => 'select',
       '#title' => t('Comment only?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       '#description' => t('Choose One'),
	   );
   $form['tasks']['s1.3']['supplemental']['s1.3-TA_allow_revisions'] = array(
       '#type' => 'select',
       '#title' => t('Optionally send back for revisions?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1.3']['supplemental']['s1.3-TA_allow_grade'] = array(
       '#type' => 'select',
       '#title' => t('Allow Grade?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1.3']['supplemental']['s1.3-TA_number_participants'] = array(
       '#type' => 'hidden',
       '#title' => t('Number of participants'),
       '#options' => array(
         0 => t('1'),
         1 => t('2'),
         2 => t('3')
		 ),
		 '#default_value' =>0,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );	


   $form['tasks']['s1.3']['supplemental']['s1.3-TA_allow_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow Dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
   $form['tasks']['s1.3']['supplemental']['s1.3-TA_allow_resolve_grades'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve grades?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
   $form['tasks']['s1.3']['supplemental']['s1.3-TA_allow_resolve_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
	$form['tasks']['s1.3']['supplemental']['s1.3-TA_leads_to_new_problem'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
	$form['tasks']['s1.3']['supplemental']['s1.3-TA_leads_to_new_solution'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new solution'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);


// Automated

  $form['tasks']['s1.3']['TA_visual_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.3']['TA_Id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.3']['TA_WA_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.3']['TA_A_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.3']['TA_version_history'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.3']['TA_refers_to_which_task'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.3']['TA_trigger_condition'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.3']['TA_next_task'] = array(
	'#type' => 'hidden',
  );
  
 //************************************************************
 //	Solve Problem ---- s1.4 ==> Grade
 //************************************************************
	$form['tasks']['s1.4'] = array(
    '#type' => 'fieldset',
    '#title' => t('Grade'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px">',
    '#suffix' => '</div>',
    '#states' => array(
		'visible' => array(
		':input[name="s1-TA_allow_grade"]' => array('value' => 1),
		),
	),
   ); 
    $form['tasks']['s1.4']['TA_type'] = array(
  	'#value' => 'grade',
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );

	$form['tasks']['s1.4']['use'] = array(
  	'#value' => TRUE,
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );
	$form['tasks']['s1.4']['basic'] = array(
    '#type' => 'fieldset',
    '#title' => t('Basic'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
  );   
  $form['tasks']['s1.4']['basic']['s1.4-TA_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Task Assignment Name:'),
    '#required' => TRUE,
    '#default_value' => "Grade",
    '#description' => "Please enter Task Assignment Name.",
    //'#size' => 20,
    //'#maxlength' => 20,
  );

  $form['tasks']['s1.4']['basic']['s1.4-TA_due'] =array(
       '#type' => 'select',
       '#title' => t('When is task due?'),
       '#options' => array(
         0 => t('# of days after last task completes'),
         1 => t('Specific Date'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option 0 
   $form['tasks']['s1.4']['basic']['s1.4-TA_due-select'] =array(
       '#type' => 'textfield',
       '#title' => 'Enter days:',
       '#default_value' => '3',
       '#states' => array(
			'visible' => array(
			':input[name="s1.4-TA_due"]' => array('value' => 0),
		),
	),
	);
   // option 1
   $form['tasks']['s1.4']['basic']['s1.4-TA_due-date_select'] =array(
	'#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Specific Date:'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="s1.4-TA_due"]' => array('value' => 1),
		),
	),
	);

// Advanced

   $form['tasks']['s1.4']['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['s1.4']['advanced']['s1.4-TA_start_time'] =array(
     '#type' => 'select',
     '#title' => t('Start when prior task completes?'),
     '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
   //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option "NO"
   $form['tasks']['s1.4']['advanced']['s1.4-TA_start_time-date_select'] =array(
	   '#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Start no earlier than specific date/time'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="s1.4-TA_start_time"]' => array('value' => 1),
		),
	),
   );
    $form['tasks']['s1.4']['advanced']['s1.4-TA_at_duration_end'] =array(
	   '#type' => 'select',
       '#title' => t('Task status at duration end'),
       '#options' => array(
         0 => t('Late'),
         1 => t('Complete'),
         2 => t('Resolved'),
		 ),
       '#default_value' => 0,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
    $form['tasks']['s1.4']['advanced']['s1.4-TA_what_if_late'] =array(
       '#type' => 'select',
       '#title' => t('What if late?'),
       '#options' => array(
         0 => t('Keep same participant'),
         1 => t('Allocate new participant'),
         2 => t('Allocate to instructor'),
         3 => t('Allocate to different group member'),
	 4 => t('Consider resolved'),
		 ),
       '#default_value' => 'Keep same participant',
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1.4']['advanced']['s1.4-TA_display_name'] = array(
   	'#type' => 'textfield',
    	'#title' => t('Task Assignment Display Name:'),
    	'#required' => TRUE,
    	'#default_value' => "Create Problem",
    	'#description' => "Please enter Task Assignment Display Name.",	
   );
   $form['tasks']['s1.4']['advanced']['s1.4-TA_description'] = array(
   	'#type' => 'textarea',
    	'#title' => t('Description'),
    	'#required' => FALSE,
    	'#description' => "Please enter Description",	
   );
   $form['tasks']['s1.4']['advanced']['s1.4-TA_one_or_seperate'] = array(
       '#type' => 'hidden',
       '#title' => t('Everyone gets same problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
  );
   $form['tasks']['s1.4']['advanced']['s1.4-TA_assignee_constraints'] = array(
       '#type' => 'select',
       '#title' => t('Who does?'),
       '#options' => array(
         0 => t('Student'),
         1 => t('Instructor'),
		 ),
       '#default_value' => 0,   
  );

// option 0

   $form['tasks']['s1.4']['advanced']['s1.4-TA_assignee_constraints-select'] = array(
        '#type' => 'radios',
        '#title' => 'Student does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="s1.4-TA_assignee_constraints"]' => array('value' => 0),
		),
	),
	);

   // option 1
   
   $form['tasks']['s1.4']['advanced']['s1.4-TA_assignee_constraints-select1'] = array(
        '#type' => 'radios',
        '#title' => 'Instructor does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="s1.4-TA_assignee_constraints"]' => array('value' => 1),
		),
	),
	);
   $form['tasks']['s1.4']['advanced']['s1.4-TA_function_type'] = array(
       '#type' => 'select',
       '#title' => t('How should the final grade be determined?'),
       '#options' => array(
         0 => t('Max'),
         1 => t('Average'),
		 ),
  );

// template

   $form['tasks']['s1.4']['template'] = array(
    '#type' => 'fieldset',
    '#title' => t('Template'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['s1.4']['template']['s1.4-TA_instructions'] = array(
   	'#type' => 'textarea',
    '#title' => t('Instructions'),
    '#required' => TRUE,
    '#default_value' => '',
    '#description' => "Please enter instructions.",	
   );
   $form['tasks']['s1.4']['template']['s1.4-TA_rubric'] = array(
   	'#type' => 'textarea',
    '#title' => t('Rubric for edit & comment'),
    '#required' => TRUE,
    '#default_value' => " ",
    	'#description' => "Please enter Rubric",	
   );


// Supplemental 

  $form['tasks']['s1.4']['supplemental'] = array(
    '#type' => 'fieldset',
    '#title' => t('Supplemental'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );   
   $form['tasks']['s1.4']['supplemental']['s1.4-TA_allow_edit+comment'] = array(
       '#type' => 'select',
       '#title' => t('Edit & comment?'),
       '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
       '#description' => t('Choose One'),
   );
   $form['tasks']['s1.4']['supplemental']['s1.4-TA_allow_comment_only'] = array(
       '#type' => 'select',
       '#title' => t('Comment only?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       '#description' => t('Choose One'),
	   );
   $form['tasks']['s1.4']['supplemental']['s1.4-TA_allow_revisions'] = array(
       '#type' => 'select',
       '#title' => t('Optionally send back for revisions?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1.4']['supplemental']['s1.4-TA_allow_grade'] = array(
       '#type' => 'select',
       '#title' => t('Allow Grade?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1.4']['supplemental']['s1.4-TA_number_participants'] = array(
       '#type' => 'hidden',
       '#title' => t('Number of participants'),
       '#options' => array(
         0 => t('1'),
         1 => t('2'),
         2 => t('3')
		 ),
		 '#default_value' => 1,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );	
	$form['tasks']['s1.4']['supplemental']['s1.4-TA_trigger_resolution_threshold'] = array(
       '#type' => 'select',
       '#title' => t('Grade resolution trigger conditions?'),
       '#options' => array(
         0 => t('Percent'),
         1 => t('Points'),
		 ),
	   '#states' => array(
			'visible' => array(
			':input[name="s1.4-TA_allow_grade"]' => array('value' => 1),
		),
		),
);
   // option "0"
   $form['tasks']['s1.4']['supplemental']['s1.4-TA_trigger_resolution_threshold-option'] = array(
	'#type' => 'textfield',
    '#title' => t('Percent Amount'),
    '#default_value' => '15',
    '#states' => array(
		'visible' => array(
		':input[name="s1.4-TA_trigger_resolution_threshold"]' => array('value' => 0),
		':input[name="s1.4-TA_allow_grade"]' => array('value' => 1),
		),
	),
   );
   // option "1"
   $form['tasks']['s1.4']['supplemental']['s1.4-TA_trigger_resolution_threshold-option1'] = array(
	'#type' => 'textfield',
    '#title' => t('Point Amount'),
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="s1.4-TA_trigger_resolution_threshold"]' => array('value' => 1),
		':input[name="s1.4-TA_allow_grade"]' => array('value' => 1),
		),
	),
   );
   $form['tasks']['s1.4']['supplemental']['s1.4-TA_allow_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow Dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       '#default_value' => 1,
	);
   $form['tasks']['s1.4']['supplemental']['s1.4-TA_allow_resolve_grades'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve grades?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
       '#default_value' => 1,
);
   $form['tasks']['s1.4']['supplemental']['s1.4-TA_allow_resolve_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
	$form['tasks']['s1.4']['supplemental']['s1.4-TA_leads_to_new_problem'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
	$form['tasks']['s1.4']['supplemental']['s1.4-TA_leads_to_new_solution'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new solution'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);


// Automated

  $form['tasks']['s1.4']['TA_visual_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.4']['TA_Id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.4']['TA_WA_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.4']['TA_A_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.4']['TA_version_history'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.4']['TA_refers_to_which_task'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.4']['TA_trigger_condition'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.4']['TA_next_task'] = array(
	'#type' => 'hidden',
  );



 //************************************************************
 //	Solve Problem ---- s1.5 ==> Resolve Grades
 //************************************************************
 
 $form['tasks']['s1.5'] = array(
    '#type' => 'fieldset',
    '#title' => t('Resolve Grade'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px">',
    '#suffix' => '</div>',
    '#states' => array(
		'visible' => array(
		':input[name="p1-TA_allow_resolve_grades"]' => array('value' => 1),
		),
	),
   ); 
    $form['tasks']['s1.5']['TA_type'] = array(
  	'#value' => 'resolve grades',
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );

	$form['tasks']['p1.5']['use'] = array(
  	'#value' => TRUE,
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );
	$form['tasks']['s1.5']['basic'] = array(
    '#type' => 'fieldset',
    '#title' => t('Basic'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
  );   
  $form['tasks']['s1.5']['basic']['s1.5-TA_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Task Assignment Name:'),
    '#required' => TRUE,
    '#default_value' => "Resolve Grades",
    '#description' => "Please enter Task Assignment Name.",
    //'#size' => 20,
    //'#maxlength' => 20,
  );
  $form['tasks']['s1.5']['basic']['s1.5-TA_due'] =array(
       '#type' => 'select',
       '#title' => t('When is task due?'),
       '#options' => array(
         0 => t('# of days after last task completes'),
         1 => t('Specific Date'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option 0 
   $form['tasks']['s1.5']['basic']['s1.5-TA_due-select'] =array(
       '#type' => 'textfield',
       '#title' => 'Enter days:',
       '#default_value' => '3',
       '#states' => array(
			'visible' => array(
			':input[name="s1.1-TA_due"]' => array('value' => 0),
		),
	),
	);
   // option 1
   $form['tasks']['s1.5']['basic']['s1.5-TA_due date_select'] =array(
	'#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Specific Date:'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="s1.5-TA_due"]' => array('value' => 1),
		),
	),
	);

// Advanced

   $form['tasks']['s1.5']['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['s1.5']['advanced']['s1.5-TA_start_time'] =array(
     '#type' => 'select',
     '#title' => t('Start when prior task completes?'),
     '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
   //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option "NO"
   $form['tasks']['s1.5']['advanced']['s1.5-TA_start_time date_select'] =array(
	   '#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Start no earlier than specific date/time'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="s1.1-TA_start_time"]' => array('value' => 1),
		),
	),
   );
    $form['tasks']['s1.5']['advanced']['s1.5-TA_at_duration_end'] =array(
	   '#type' => 'select',
       '#title' => t('Task status at duration end'),
       '#options' => array(
         0 => t('Late'),
         1 => t('Complete'),
         2 => t('Resolved'),
		 ),
       '#default_value' => 0,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
    $form['tasks']['s1.5']['advanced']['s1.5-TA_what_if_late'] =array(
       '#type' => 'select',
       '#title' => t('What if late?'),
       '#options' => array(
         0 => t('Keep same participant'),
         1 => t('Allocate new participant'),
         2 => t('Allocate to instructor'),
         3 => t('Allocate to different group member'),
	 4 => t('Consider resolved'),
		 ),
       '#default_value' => 'Keep same participant',
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1.5']['advanced']['s1.5-TA_display_name'] = array(
   	'#type' => 'textfield',
    	'#title' => t('Task Assignment Display Name:'),
    	'#required' => TRUE,
    	'#default_value' => "Create Problem",
    	'#description' => "Please enter Task Assignment Display Name.",	
   );
   $form['tasks']['s1.5']['advanced']['s1.5-TA_description'] = array(
   	'#type' => 'textarea',
    	'#title' => t('Description'),
    	'#required' => FALSE,
    	'#description' => "Please enter Description",	
   );
   $form['tasks']['s1.5']['advanced']['s1.5-TA_one_or_seperate'] = array(
       '#type' => 'hidden',
       '#title' => t('Everyone gets same problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
  );
   $form['tasks']['s1.5']['advanced']['s1.5-TA_assignee_constraints'] = array(
       '#type' => 'select',
       '#title' => t('Who does?'),
       '#options' => array(
         0 => t('Student'),
         1 => t('Instructor'),
		 ),   
  );

// option 0

   $form['tasks']['s1.5']['advanced']['s1.5-TA_assignee_constraints-select'] = array(
        '#type' => 'radios',
        '#title' => 'Student does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="s1.5-TA_assignee_constraints"]' => array('value' => 0),
		),
	),
	);

   // option 1
   
   $form['tasks']['s1.5']['advanced']['s1.5-TA_assignee_constraints-select1'] = array(
        '#type' => 'radios',
        '#title' => 'Instructor does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="s1.5-TA_assignee_constraints"]' => array('value' => 1),
		),
	),
	);
   $form['tasks']['s1.5']['advanced']['s1.5-TA_function_type'] = array(
	
   );

// template

   $form['tasks']['s1.5']['template'] = array(
    '#type' => 'fieldset',
    '#title' => t('Template'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['s1.5']['template']['s1.5-TA_instructions'] = array(
   	'#type' => 'textarea',
    '#title' => t('Instructions'),
    '#required' => TRUE,
    '#default_value' => 'Because the regular graders did give the same '
          .'grade, please resolve the grade disagreement. Assign your '
          .'own score and justification for each part of the grade, and also '
          .'please provide an explanation.',
    '#description' => "Please enter instructions.",	
   );
   $form['tasks']['s1.5']['template']['s1.5-TA_rubric'] = array(
   	'#type' => 'textarea',
    '#title' => t('Rubric for edit & comment'),
    '#required' => TRUE,
    '#default_value' => " ",
    	'#description' => "Please enter Rubric",	
   );


// Supplemental 

  $form['tasks']['s1.5']['supplemental'] = array(
    '#type' => 'fieldset',
    '#title' => t('Supplemental'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );   
   $form['tasks']['s1.5']['supplemental']['s1.5-TA_allow_edit+comment'] = array(
       '#type' => 'select',
       '#title' => t('Edit & comment?'),
       '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
       '#description' => t('Choose One'),
   );
   $form['tasks']['s1.5']['supplemental']['s1.5-TA_allow_comment_only'] = array(
       '#type' => 'select',
       '#title' => t('Comment only?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       '#description' => t('Choose One'),
	   );
   $form['tasks']['s1.5']['supplemental']['s1.5-TA_allow_revisions'] = array(
       '#type' => 'select',
       '#title' => t('Optionally send back for revisions?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1.5']['supplemental']['p1.5-TA_allow_grade'] = array(
       '#type' => 'select',
       '#title' => t('Allow Grade?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1.5']['supplemental']['s1.5-TA_number_participants'] = array(
       '#type' => 'hidden',
       '#title' => t('Number of participants'),
       '#options' => array(
         0 => t('1'),
         1 => t('2'),
         2 => t('3')
		 ),
		 '#default_value' => 1,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );	
   $form['tasks']['s1.5']['supplemental']['s1.5-TA_allow_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow Dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
   $form['tasks']['p1.5']['supplemental']['p1.5-TA_allow_resolve_grades'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve grades?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
   $form['tasks']['s1.5']['supplemental']['s1.5-TA_allow_resolve_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
	$form['tasks']['s1.5']['supplemental']['s1.5-TA_leads_to_new_problem'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
	$form['tasks']['s1.5']['supplemental']['s1.5-TA_leads_to_new_solution'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new solution'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);


// Automated

  $form['tasks']['s1.5']['TA_visual_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.5']['TA_Id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.5']['TA_WA_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.5']['TA_A_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.5']['TA_version_history'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.5']['TA_refers_to_which_task'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.5']['TA_trigger_condition'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.5']['TA_next_task'] = array(
	'#type' => 'hidden',
  );


 //************************************************************
 //	Solve Problem ---- s1.6 ==> Dispute
 //************************************************************
	$form['tasks']['s1.6'] = array(
    '#type' => 'fieldset',
    '#title' => t('Dispute'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px">',
    '#suffix' => '</div>',
    '#states' => array(
		'visible' => array(
		':input[name="s1-TA_allow_dispute"]' => array('value' => 1),
		),
	),
   ); 
    $form['tasks']['s1.6']['TA_type'] = array(
  	'#value' => 'dispute',
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );

	$form['tasks']['s1.6']['use'] = array(
  	'#value' => TRUE,
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );
	$form['tasks']['s1.6']['basic'] = array(
    '#type' => 'fieldset',
    '#title' => t('Basic'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
  );   
  $form['tasks']['s1.6']['basic']['s1.6-TA_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Task Assignment Name:'),
    '#required' => TRUE,
    '#default_value' => "Dispute",
    '#description' => "Please enter Task Assignment Name.",
    //'#size' => 20,
    //'#maxlength' => 20,
  );
  $form['tasks']['s1.6']['basic']['s1.6-TA_due'] =array(
       '#type' => 'select',
       '#title' => t('When is task due?'),
       '#options' => array(
         0 => t('# of days after last task completes'),
         1 => t('Specific Date'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option 0 
   $form['tasks']['s1.6']['basic']['s1.6-TA_due select'] =array(
       '#type' => 'textfield',
       '#title' => 'Enter days:',
       '#default_value' => '3',
       '#states' => array(
			'visible' => array(
			':input[name="s1.6-TA_due"]' => array('value' => 0),
		),
	),
	);
   // option 1
   $form['tasks']['s1.6']['basic']['s1.6-TA_due date_select'] =array(
	'#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Specific Date:'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="s1.6-TA_due"]' => array('value' => 1),
		),
	),
	);

// Advanced

   $form['tasks']['s1.6']['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['s1.6']['advanced']['s1.6-TA_start_time'] =array(
     '#type' => 'select',
     '#title' => t('Start when prior task completes?'),
     '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
   //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option "NO"
   $form['tasks']['s1.6']['advanced']['s1.6-TA_start_time date_select'] =array(
	   '#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Start no earlier than specific date/time'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="s1.6-TA_start_time"]' => array('value' => 1),
		),
	),
   );
    $form['tasks']['s1.6']['advanced']['s1.6-TA_at_duration_end'] =array(
	   '#type' => 'select',
       '#title' => t('Task status at duration end'),
       '#options' => array(
         0 => t('Late'),
         1 => t('Complete'),
         2 => t('Resolved'),
		 ),
       '#default_value' => 2,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
    $form['tasks']['s1.6']['advanced']['s1.6-TA_what_if_late'] =array(
       '#type' => 'select',
       '#title' => t('What if late?'),
       '#options' => array(
         0 => t('Keep same participant'),
         1 => t('Allocate new participant'),
         2 => t('Allocate to instructor'),
         3 => t('Allocate to different group member'),
	 4 => t('Consider resolved'),
		 ),
       '#default_value' => 4,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1.6']['advanced']['s1.6-TA_display_name'] = array(
   	'#type' => 'textfield',
    	'#title' => t('Task Assignment Display Name:'),
    	'#required' => TRUE,
    	'#default_value' => "Create Problem",
    	'#description' => "Please enter Task Assignment Display Name.",	
   );
   $form['tasks']['s1.6']['advanced']['s1.6-TA_description'] = array(
   	'#type' => 'textarea',
    	'#title' => t('Description'),
    	'#required' => FALSE,
    	'#description' => "Please enter Description",	
   );
   $form['tasks']['s1.6']['advanced']['s1.6-TA_one_or_seperate'] = array(
       '#type' => 'hidden',
       '#title' => t('Everyone gets same problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
  );
  $form['tasks']['s1.6']['advanced']['s1.6-TA_assignee_constraints'] = array(
       '#type' => 'select',
       '#title' => t('Who does?'),
       '#options' => array(
         0 => t('Student'),
         1 => t('Instructor'),
		 ),   
  );

// option 0

   $form['tasks']['s1.6']['advanced']['s1.6-TA_assignee_constraints-select'] = array(
        '#type' => 'radios',
        '#title' => 'Student does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="s1.6-TA_assignee_constraints"]' => array('value' => 0),
		),
	),
	);

   // option 1
   
   $form['tasks']['s1.6']['advanced']['s1.6-TA_assignee_constraint-select1'] = array(
        '#type' => 'radios',
        '#title' => 'Instructor does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="s1.6-TA_assignee_constraints"]' => array('value' => 1),
		),
	),
	);
	
   $form['tasks']['s1.6']['advanced']['s1.6-TA_function_type'] = array(
	
   );

// template

   $form['tasks']['s1.6']['template'] = array(
    '#type' => 'fieldset',
    '#title' => t('Template'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['s1.6']['template']['s1.6-TA_instructions'] = array(
   	'#type' => 'textarea',
    '#title' => t('Instructions'),
    '#required' => TRUE,
    '#default_value' => 'You have the option to dispute your grade. To do '
          .'so, you need to fully grade your own solution. Assign your own '
          .'score and justification for each part of the grade. You must also '
          .'explain why the other graders were wrong.',
    '#description' => "Please enter instructions.",	
   );
   $form['tasks']['s1.6']['template']['s1.6-TA_rubric'] = array(
   	'#type' => 'textarea',
    '#title' => t('Rubric for edit & comment'),
    '#required' => TRUE,
    '#default_value' => " ",
    	'#description' => "Please enter Rubric",	
   );


// Supplemental 

  $form['tasks']['s1.6']['supplemental'] = array(
    '#type' => 'fieldset',
    '#title' => t('Supplemental'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );   
   $form['tasks']['s1.6']['supplemental']['s1.6-TA_allow_edit+comment'] = array(
       '#type' => 'select',
       '#title' => t('Edit & comment?'),
       '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
       '#description' => t('Choose One'),
   );
   $form['tasks']['s1.6']['supplemental']['s1.6-TA_allow_comment_only'] = array(
       '#type' => 'select',
       '#title' => t('Comment only?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       '#description' => t('Choose One'),
	   );
   $form['tasks']['s1.6']['supplemental']['s1.6-TA_allow_revisions'] = array(
       '#type' => 'select',
       '#title' => t('Optionally send back for revisions?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1.6']['supplemental']['s1.6-TA_allow_grade'] = array(
       '#type' => 'select',
       '#title' => t('Allow Grade?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1.6']['supplemental']['s1.6-TA_number_participants'] = array(
       '#type' => 'hidden',
       '#title' => t('Number of participants'),
       '#options' => array(
         0 => t('1'),
         1 => t('2'),
         2 => t('3')
		 ),
		 '#default_value' => 1,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );	


   $form['tasks']['s1.6']['supplemental']['s1.6-TA_allow_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow Dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
   $form['tasks']['s1.6']['supplemental']['s1.6-TA_allow_resolve_grades'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve grades?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
   $form['tasks']['s1.6']['supplemental']['s1.6-TA_allow_resolve_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
	'#default_value' => 1,
);
	$form['tasks']['s1.6']['supplemental']['s1.6-TA_leads_to_new_problem'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
	$form['tasks']['s1.6']['supplemental']['s1.6-TA_leads_to_new_solution'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new solution'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);


// Automated

  $form['tasks']['s1.6']['TA_visual_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.6']['TA_Id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.6']['TA_WA_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.6']['TA_A_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.6']['TA_version_history'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.6']['TA_refers_to_which_task'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.6']['TA_trigger_condition'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.6']['TA_next_task'] = array(
	'#type' => 'hidden',
  );


 //************************************************************
 //	Solve Problem ---- s1.7 ==> Resolve Dispute
 //************************************************************
	$form['tasks']['s1.7'] = array(
    '#type' => 'fieldset',
    '#title' => t('Resolve Dispute'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px">',
    '#suffix' => '</div>',
    '#states' => array(
		'visible' => array(
		':input[name="s1-TA_allow_resolve_dispute"]' => array('value' => 1),
		),
	),
   ); 
    $form['tasks']['s1.7']['TA_type'] = array(
  	'#value' => 'resolve dispute',
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );

	$form['tasks']['s1.7']['use'] = array(
  	'#value' => TRUE,
    '#type' => 'hidden',
    '#collapsible' => FALSE,
    );
	$form['tasks']['s1.7']['basic'] = array(
    '#type' => 'fieldset',
    '#title' => t('Basic'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
  );   
 $form['tasks']['s1.7']['basic']['s1.7-TA_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Task Assignment Name:'),
    '#required' => TRUE,
    '#default_value' => "Resolve Dispute",
    '#description' => "Please enter Task Assignment Name.",
    //'#size' => 20,
    //'#maxlength' => 20,
  );
  $form['tasks']['s1.7']['basic']['s1.7-TA_due'] =array(
       '#type' => 'select',
       '#title' => t('When is task due?'),
       '#options' => array(
         0 => t('# of days after last task completes'),
         1 => t('Specific Date'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option 0 
   $form['tasks']['s1.7']['basic']['s1.7-TA_due select'] =array(
       '#type' => 'textfield',
       '#title' => 'Enter days:',
       '#default_value' => '3',
       '#states' => array(
			'visible' => array(
			':input[name="s1.7-TA_due"]' => array('value' => 0),
		),
	),
	);
   // option 1
   $form['tasks']['s1.7']['basic']['s1.7-TA_due date_select'] =array(
	'#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Specific Date:'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="s1.7-TA_due"]' => array('value' => 1),
		),
	),
	);

// Advanced

   $form['tasks']['s1.7']['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['s1.7']['advanced']['s1.7-TA_start_time'] =array(
     '#type' => 'select',
     '#title' => t('Start when prior task completes?'),
     '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
   //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option "NO"
   $form['tasks']['s1.7']['advanced']['s1.7-TA_start_time date_select'] =array(
	   '#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Start no earlier than specific date/time'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="s1.7-TA_start_time"]' => array('value' => 1),
		),
	),
   );
    $form['tasks']['s1.7']['advanced']['s1.7-TA_at_duration_end'] =array(
	   '#type' => 'select',
       '#title' => t('Task status at duration end'),
       '#options' => array(
         0 => t('Late'),
         1 => t('Complete'),
         2 => t('Resolved'),
		 ),
       '#default_value' => 0,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
    $form['tasks']['s1.7']['advanced']['s1.7-TA_what_if_late'] =array(
       '#type' => 'select',
       '#title' => t('What if late?'),
       '#options' => array(
         0 => t('Keep same participant'),
         1 => t('Allocate new participant'),
         2 => t('Allocate to instructor'),
         3 => t('Allocate to different group member'),
	 4 => t('Consider resolved'),
		 ),
       '#default_value' => 'Keep same participant',
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1.7']['advanced']['s1.7-TA_display_name'] = array(
   	'#type' => 'textfield',
    	'#title' => t('Task Assignment Display Name:'),
    	'#required' => TRUE,
    	'#default_value' => "Create Problem",
    	'#description' => "Please enter Task Assignment Display Name.",	
   );
   $form['tasks']['s1.7']['advanced']['s1.7-TA_description'] = array(
   	'#type' => 'textarea',
    	'#title' => t('Description'),
    	'#required' => FALSE,
    	'#description' => "Please enter Description",	
   );
   $form['tasks']['s1.7']['advanced']['s1.7-TA_one_or_seperate'] = array(
       '#type' => 'hidden',
       '#title' => t('Everyone gets same problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
  );
   $form['tasks']['s1.7']['advanced']['s1.7-TA_assignee_constraints'] = array(
       '#type' => 'select',
       '#title' => t('Who does?'),
       '#options' => array(
         0 => t('Student'),
         1 => t('Instructor'),
		 ),
	'#default_value' => 1,   
  );

// option 0

   $form['tasks']['s1.7']['advanced']['s1.7-TA_assignee_constraints select'] = array(
        '#type' => 'radios',
        '#title' => 'Student does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
    	'#states' => array(
		'visible' => array(
		':input[name="s1.7-TA_assignee_constraints"]' => array('value' => 0),
		),
	),
	);

   // option 1
   
   $form['tasks']['s1.7']['advanced']['s1.7-TA_assignee_constraints select1'] = array(
        '#type' => 'radios',
        '#title' => 'Instructor does work:',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 1,
    	'#states' => array(
		'visible' => array(
		':input[name="p1.7-TA_assignee_constraints"]' => array('value' => 1),
		),
	),
	);
   $form['tasks']['s1.7']['advanced']['s1.7-TA_function_type'] = array(
	
   );

// template

   $form['tasks']['s1.7']['template'] = array(
    '#type' => 'fieldset',
    '#title' => t('Template'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $form['tasks']['s1.7']['template']['s1.7-TA_instructions'] = array(
   	'#type' => 'textarea',
    '#title' => t('Instructions'),
    '#required' => TRUE,
    '#default_value' => 'The problem solver is disputing his or her grade. '
          .'You need to provide the final grade. Assign a final score with '
          .'justification for each part of the grade, and also please provide '
          .'an explanation.',
    '#description' => "Please enter instructions.",	
   );
   $form['tasks']['s1.7']['template']['s1.7-TA_rubric'] = array(
   	'#type' => 'textarea',
    '#title' => t('Rubric for edit & comment'),
    '#required' => TRUE,
    '#default_value' => " ",
    	'#description' => "Please enter Rubric",	
   );


// Supplemental 

  $form['tasks']['s1.7']['supplemental'] = array(
    '#type' => 'fieldset',
    '#title' => t('Supplemental'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );   
   $form['tasks']['s1.7']['supplemental']['s1.7-TA_allow_edit+comment'] = array(
       '#type' => 'select',
       '#title' => t('Edit & comment?'),
       '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
       '#description' => t('Choose One'),
   );
   $form['tasks']['s1.7']['supplemental']['s1.7-TA_allow_comment_only'] = array(
       '#type' => 'select',
       '#title' => t('Comment only?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       '#description' => t('Choose One'),
	   );
   $form['tasks']['s1.7']['supplemental']['s1.7-TA_allow_revisions'] = array(
       '#type' => 'select',
       '#title' => t('Optionally send back for revisions?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1.7']['supplemental']['s1.7-TA_allow_grade'] = array(
       '#type' => 'select',
       '#title' => t('Allow Grade?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $form['tasks']['s1.7']['supplemental']['s1.7-TA_number_participants'] = array(
       '#type' => 'hidden',
       '#title' => t('Number of participants'),
       '#options' => array(
         0 => t('1'),
         1 => t('2'),
         2 => t('3')
		 ),
		 '#default_value' => 1,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );	

   $form['tasks']['s1.7']['supplemental']['s1.7-TA_allow_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow Dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
   $form['tasks']['s1.7']['supplemental']['s1.7-TA_allow_resolve_grades'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve grades?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
   $form['tasks']['s1.7']['supplemental']['s1.7-TA_allow_resolve_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
	$form['tasks']['s1.7']['supplemental']['s1.7-TA_leads_to_new_problem'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
	$form['tasks']['s1.7']['supplemental']['s1.7-TA_leads_to_new_solution'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new solution'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);


// Automated

  $form['tasks']['s1.7']['TA_visual_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.7']['TA_Id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.7']['TA_WA_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.7']['TA_A_id'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.7']['TA_version_history'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.7']['TA_refers_to_which_task'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.7']['TA_trigger_condition'] = array(
	'#type' => 'hidden',
  );

  $form['tasks']['s1.7']['TA_next_task'] = array(
	'#type' => 'hidden',
  );
*/
// Submit Stuff
 // $tasks = array();
 

 
  // New form field added to permit entry of year of birth.
  // The data entered into this field will be validated with
  // the default validation function.
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Submit',
  );
 // $form['save'] = array(
   // '#type' => 'submit',
  //  '#value' => 'Save For Later',
//  );
  return $form;
}

function groupgrade_createproblem_submit($form, &$form_state) {
	
$task = form_process_fieldset($form['tasks'],$form_state);

  foreach($task as $key => $value){
    
	if(substr($key,0,1) == '#' || strlen($key) < 2)
	  continue;
	
	//drupal_set_message($key);

	$TA_type = $form['tasks'][$key]['TA_type']['#value'];
	drupal_set_message($TA_type);	   
	$TA_name = $form['tasks'][$key]['basic'][$key . '-TA_name']['#value'];
	drupal_set_message($TA_name);
	// Assignmant name
	if(isset($form['tasks'][$key]['basic'][$key . '-A_name']['#value'])){
	$TA_A_name = $form['tasks'][$key]['basic'][$key . '-A_name']['#value'];
		drupal_set_message($TA_A_name);
	}
	// Assignment type
	if(isset($form['tasks'][$key]['basic'][$key . '-A_type']['#value'])){
	$TA_A_type = $form['tasks'][$key]['basic'][$key . '-A_type']['#value'];
		drupal_set_message($TA_A_type);	
	}
	// not working	
	$TA_due = $form['tasks'][$key]['basic'][$key . '-TA_due']['#value'];

	if($TA_due == 0){
		$TA_due_select = $form['tasks'][$key]['basic'][$key . '-TA_due_select']['#value'];
		drupal_set_message($form['tasks'][$key]['basic'][$key . '-TA_due_select']['#value']);
	}
	else {
		// Date
		$start = $form['tasks'][$key]['basic'][$key.'-TA_due_date_select']['#value'];		
		foreach (['year', 'month', 'day', 'hour', 'minute'] as $i) :
		    if ($start[$i] == '')
		      return drupal_set_message("Invalid date setting for " . $key . " task",'error');
		    elseif ((int) $start[$i] < 9)
		      $start[$i] = '0'.intval($start[$i]);
		    else
		      $start[$i] = (string) $start[$i];
			
			if ($i == 'year' AND intval($start[$i]) == 0)
      		  $start['year'] = (string) date('Y');
			
		endforeach;		
	$TA_due_date_select[$key]['-TA_due_date_select'] = sprintf('%s-%s-%s %s:%s:00', $start['year'], $start['month'], $start['day'], $start['hour'], $start['minute']);
		drupal_set_message($TA_due_date_select);
		//drupal_set_message($key . '-' .  sprintf("%d days after triggering",$TA_due_select));
	}
	// HAVE to DO
	$TA_start_time = $form['tasks'][$key]['advanced'][$key . '-start_time']['#value'];
		// drupal_set_message($key . '-' . $TA_due);
	// drupal_set_message($key . '-' . $TA_name);
	// drupal_set_message($key . '-' . $TA_type);
	$TA_at_duration_end = $form['tasks'][$key]['advanced'][$key . '-TA_at_duration_end']['#value'];	
	$TA_what_if_late = $form['tasks'][$key]['advanced'][$key . '$TA_what_if_late']['#value'];		
}
 
  
drupal_set_message(t('The form has been submitted.'));

  return;
}  	
/*
  $TA_Tname = $form['tasks']['p1']['basic']['p1-TA_name']['#value'];
  $TA_name = $form['tasks']['p1']['basic']['p1-A_name']['#value'];
  //$TA_start_time = $form['tasks']['p1']['advanced']['p1-start_time']['#value'];
  
  $TA_at_duration_end = $form['tasks']['p1']['advanced']['#value'];
  $TA_what_if_late = $form['tasks']['p1']['advanced']['#value'];
 $TA_display_name = $form['tasks']['p1']['advanced']['#value'];
  $TA_description = $form['tasks']['p1']['advanced']['#value'];
  $TA_one_or_seperate = $form['tasks']['p1']['advanced']['#value'];
  $TA_assignee_constraints = $form['tasks']['p1']['advanced']['#value'];
  $TA_function_type = $form['tasks']['p1']['advanced']['#value'];
  $TA_instructions = $form['tasks']['p1']['template']['#value'];
  $TA_rubric = $form['tasks']['p1']['template']['#value'];
  $TA_allow_edit_comment = $form['tasks']['p1']['supplemental']['#value'];
  $TA_allow_comment_only = $form['tasks']['p1']['supplemental']['#value'];
  $TA_allow_revisions = $form['tasks']['p1']['supplemental']['#value'];
  $TA_allow_grade = $form['tasks']['p1']['supplemental']['#value'];
  $TA_number_of_participants = $form['tasks']['p1']['supplemental']['#value'];
  $TA_trigger_resolution_threshold = $form['tasks']['p1']['supplemental']['#value'];
  $TA_allow_dispute = $form['tasks']['p1']['supplemental']['#value'];
  $TA_allow_resolve_grade = $form['tasks']['p1']['supplemental']['#value'];
  $TA_allow_resolve_dispute = $form['tasks']['p1']['supplemental']['#value'];
  $TA_leads_to_new_problem = $form['tasks']['p1']['supplemental']['#value'];
  $TA_leads_to_new_solution = $form['tasks']['p1']['supplemental']['#value'];
 */