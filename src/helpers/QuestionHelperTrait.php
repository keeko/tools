<?php
namespace keeko\tools\helpers;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
/**
 * Helper for questions
 * 
 * Original: https://github.com/composer/composer/blob/master/src/Composer/Command/Helper/DialogHelper.php
 */
trait QuestionHelperTrait {
	
	/**
	 * Build text for asking a question. For example:
	 *
	 * "Do you want to continue [yes]:"
	 *
	 * @param string $question The question you want to ask
	 * @param mixed $default Default value to add to message, if false no default will be shown
	 * @param string $sep Separation char for between message and user input
	 *
	 * @return string
	 */
	protected function getQuestion($question, $default = null, $sep = ':') {
		return !empty($default) ?
			sprintf('<info>%s</info> [<comment>%s</comment>]%s ', $question, $default, $sep) :
			sprintf('<info>%s</info>%s ', $question, $sep);
	}
	
	/**
	 * @return HelperSet
	 */
	abstract protected function getHelperSet();
	
	abstract protected function getInput();
	
	abstract protected function getOutput();
	
	private $dialog;
	
	private function ask(Question $question) {
		if ($this->dialog === null) {
			$this->dialog = $this->getHelperSet()->get('question');
		}
		$input = $this->getInput();
		$output = $this->getOutput();
		return $this->dialog->ask($input, $output, $question);
	}
	
	protected function askQuestion(Question $question) {
		$default = $question->getDefault();
		$q = $this->getQuestion($question->getQuestion(), $default);
		$q = new Question($q, $default);
		$q->setAutocompleterValues($question->getAutocompleterValues());
		return $this->ask($q);
	}
	
	protected function askConfirmation(ConfirmationQuestion $question) {
		$default = 'y/n';
		$q = $this->getQuestion($question->getQuestion(), $default);
		return $this->ask(new ConfirmationQuestion($q, $default));
	}
	
}