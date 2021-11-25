<?php

namespace Drupal\koval\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class that provide table form.
 */
class TableKovalForm extends FormBase {

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->setMessenger($container->get('messenger'));
    return $instance;
  }

  /**
   * Initial number of rows.
   *
   * This variable is the row counter in the table.
   *
   * @var int
   */
  protected $rows = 1;

  /**
   * Initial number of tables.
   *
   * This variable is a counter of the number of tables.
   *
   * @var int
   */
  protected $tables = 1;

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'koval_form_table';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="koval-form-table">';
    $form['#suffix'] = '</div>';

    $form['add_table'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add table'),
      '#submit' => [
        '::addNewTable',
      ],
      '#ajax' => [
        'wrapper' => 'koval-form-table',
      ],
    ];

    $form['add_row'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add row'),
      '#submit' => [
        '::addNewRow',
      ],
      '#ajax' => [
        'wrapper' => 'koval-form-table',
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'wrapper' => 'koval-form-table',
      ],
    ];

    // Call the function that builds table.
    $this->tableConstruction($form, $form_state);
    $form['#attached']['library'][] = 'koval/koval_style';
    return $form;
  }

  /**
   * A function that returns a table header.
   *
   * return @array
   */
  public function headerConstruction() {
    $header = [
      'year' => $this->t('Year'),
      'jan' => $this->t('Jan'),
      'feb' => $this->t('Feb'),
      'mar' => $this->t('Mar'),
      'q1' => $this->t('Q1'),
      'apr' => $this->t('Apr'),
      'may' => $this->t('May'),
      'jun' => $this->t('Jun'),
      'q2' => $this->t('Q2'),
      'jul' => $this->t('Jul'),
      'aug' => $this->t('Aug'),
      'sep' => $this->t('Sep'),
      'q3' => $this->t('Q3'),
      'oct' => $this->t('Oct'),
      'nov' => $this->t('Nov'),
      'dec' => $this->t('Dec'),
      'q4' => $this->t('Q4'),
      'ytd' => $this->t('YTD'),
    ];
    return $header;
  }

  /**
   * A function that returns the keys of inactive cells in a table.
   */
  public function inactiveTableCells() {
    $inactive_header_cels = [
      'q1' => '',
      'q2' => '',
      'q3' => '',
      'q4' => '',
      'year' => '',
      'ytd' => '',
    ];
    return $inactive_header_cels;
  }

  /**
   * A function that converts an associative array to a normal one.
   */
  public function transformTableArray($array) {
    // An array that will be filled with cell values in the table.
    $values = [];
    $inactive_cells = $this->inactiveTableCells();
    // We pass on rows of the table.
    for ($i = $this->rows; $i >= 1; $i--) {
      // We get the value of the cells.
      foreach ($array[$i] as $key => $value) {
        // If the cell key is not equal to the inactive cell key.
        // Then we get the value.
        if (!array_key_exists($key, $inactive_cells)) {
          $values[] = $value;
        }
      }
    }
    return $values;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // We start a cycle through which we will sort values of all tables.
    for ($i = 0; $i < $this->tables; $i++) {
      // We get the value of the current form.
      $table_values = $form_state->getValue($i);
      // We receive and process all values of the table.
      foreach ($table_values as $key => $value) {
        $cell = '][' . $key . '][' . $i;
        // Calculate the value of quarters.
        $q1 = (((int) $value['jan'] + (int) $value['feb'] + (int) $value['mar']) + 1) / 3;
        $q2 = (((int) $value['apr'] + (int) $value['may'] + (int) $value['jun']) + 1) / 3;
        $q3 = (((int) $value['jul'] + (int) $value['aug'] + (int) $value['sep']) + 1) / 3;
        $q4 = (((int) $value['oct'] + (int) $value['nov'] + (int) $value['dec']) + 1) / 3;
        $year = ((int) $q1 + (int) $q2 + (int) $q3 + (int) $q4 + 1) / 4;
        // Insert the obtained values into the corresponding cells in the table.
        $form_state->setValue('q1' . $cell, $q1);
        $form_state->setValue('q2' . $cell, $q2);
        $form_state->setValue('q3' . $cell, $q3);
        $form_state->setValue('q4' . $cell, $q4);
        $form_state->setValue('ytd' . $cell, $year);
      }
    }
    $form_state->setRebuild();
    $this->messenger()->addStatus('Valid');
  }

  /**
   * Adds a new row.
   *
   * The variable rows is plus, and a row is added.
   */
  public function addNewRow(array &$form, FormStateInterface $form_state) {
    $this->rows++;
    $form_state->setRebuild();
    return $form;
  }

  /**
   * Adds a new table.
   *
   * The variable tables is plus, and a table is added.
   */
  public function addNewTable(array &$form, FormStateInterface $form_state) {
    $this->tables++;
    $form_state->setRebuild();
    return $form;
  }

  /**
   * Builds the structure of a table.
   */
  public function tableConstruction(array &$form, FormStateInterface $form_state) {
    // We get the header of the table.
    $cell_headers = $this->headerConstruction();
    // We go through a cycle, thereby building our table.
    for ($num_table = 0; $num_table < $this->tables; $num_table++) {
      $key_table = $num_table;
      $form[$key_table] = [
        '#type' => 'table',
        '#header' => $cell_headers,
        '#default_value' => 0,
      ];
      $this->tableRowConstruction($form[$key_table], $form_state, $key_table);
    }
  }

  /**
   * Builds our rows in table.
   */
  public function tableRowConstruction(array &$table, FormStateInterface $form_state, $table_key) {
    $cell_headers = $this->headerConstruction();
    // We get an array with field keys, these keys are needed
    // in order to find the cells we need in the table,
    // and disable them with disabled.
    $inactive_cell = $this->inactiveTableCells();
    // We go through a loop, thereby constructing our rows in the table.
    for ($num_row = $this->rows; $num_row > 0; $num_row--) {
      // Create cells in a row.
      foreach ($cell_headers as $key => $value) {
        $table[$num_row][$key] = [
          '#type' => 'number',
          '#step' => 0.01,
        ];
        // We get the value of the year.
        $table[$num_row]['year']['#default_value'] = date('Y') - $num_row + 1;
        // If the key is the key of an inactive cell,
        // then we disable this field, and make a default value of 0.
        if (array_key_exists($key, $inactive_cell)) {
          $cell_value = $form_state->getValue($key . '][' . $num_row . '][' . $table_key, 0);
          // The obtained values are rounded to two values after the comma.
          $table[$num_row][$key]['#default_value'] = round($cell_value, 2);
          // Turn off the cell.
          $table[$num_row][$key]['#attributes']['disabled'] = TRUE;
        }
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $table_values = $form_state->getValues();
    // The starting point from which non-empty fields begin.
    $start_cell = NULL;
    // The end point at which non-empty fields end.
    $finish_cell = NULL;
    // An array where the values of all tables will be stored.
    $all_tables_values = [];
    // We start a cycle for passage of all tables.
    for ($i = 0; $i < $this->tables; $i++) {
      // We obtain an array of values of the current table.
      $values = $this->transformTableArray($table_values[$i]);
      // Save the table in an array.
      $all_tables_values[] = $values;
      // We go through all the cells in the current table.
      foreach ($values as $key => $value) {
        // If there is one row in the table.
        if ($this->rows === 1) {
          // We start a cycle, and we pass through cells in the first row.
          for ($cell_key = 0; $cell_key < 12; $cell_key++) {
            // Check if the cell is empty in one table and not in the other.
            if (empty($all_tables_values[0][$cell_key]) !== empty($all_tables_values[$i][$cell_key])) {
              $form_state->setErrorByName($i, 'Tables must be the same!');
            }
          }
        }

        // If the cell is not empty, then non-empty fields start from this cell.
        if (!empty($value)) {
          $start_cell = $key;
          break;
        }
      }

      // If the starting point exists.
      if ($start_cell !== NULL) {
        // We go through the cells until the cell is empty.
        for ($filled_cell = $start_cell; $filled_cell < count($values) + 1; $filled_cell++) {
          // If the cell is empty,
          // then it is the end point at which the non-empty fields end.
          if (empty($values[$filled_cell])) {
            $finish_cell = $filled_cell;
            break;
          }
        }
      }

      // If the endpoint exists.
      if ($finish_cell !== NULL) {
        // We go through the cells after the end point.
        for ($empty_cell = $finish_cell; $empty_cell < count($values) + 1; $empty_cell++) {
          // If the cell is not empty, the table is not valid.
          if (!empty($values[$empty_cell])) {
            $form_state->setError($form[$i], 'Invalid');
          }
        }
      }
    }
  }

}
