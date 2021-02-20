<?php

namespace DevHelper\Util\Autogen;

use XF\PrintableException;
use DevHelper\Util\AutogenContext;
use XF\Entity\Phrase as EntityPhrase;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Output\OutputInterface;

class Phrase
{
    /**
     * @param AutogenContext $context
     * @param string $title
     * @return EntityPhrase
     * @throws PrintableException
     */
    public static function autogen($context, $title)
    {
        /** @var EntityPhrase|null $existing */
        $existing = $context->finder('XF:Phrase')
            ->where('language_id', 0)
            ->where('addon_id', $context->getAddOnId())
            ->where('title', $title)
            ->fetchOne();
        if ($existing !== null) {
            $context->writeln(
                "<info>Phrase #{$existing->phrase_id} {$existing->title} = {$existing->phrase_text} OK</info>",
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            return $existing;
        }

        /** @var EntityPhrase $newPhrase */
        $newPhrase = $context->createEntity('XF:Phrase');
        $newPhrase->addon_id = $context->getAddOnId();
        $newPhrase->language_id = 0;
        $newPhrase->title = $title;

        $questionText = sprintf('<question>Enter phrase text for %s:</question> ', $title);
        $question = new Question($questionText);
        $newPhrase->phrase_text = $context->ask($question);

        $newPhrase->save();
        $context->writeln("<info>Phrase #{$newPhrase->phrase_id} {$newPhrase->title} = {$newPhrase->phrase_text} NEW</info>");

        return $newPhrase;
    }
}
