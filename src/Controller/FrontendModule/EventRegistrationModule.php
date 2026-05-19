<?php

namespace App\Controller\FrontendModule;

use App\Model\EventRegistration;
use Contao\CalendarEventsModel;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FormFieldModel;
use Contao\ModuleModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsFrontendModule(category: 'events', template: 'frontend_module/event_registration_module')]
class EventRegistrationModule extends AbstractFrontendModuleController
{
    public function __construct(private readonly MailerInterface $mailer)
    {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $token = $request->query->get('token', '');

        if (empty($token)) {
            $template->error = 'missing_token';

            return $template->getResponse();
        }

        $event = CalendarEventsModel::findOneBy(
            ['regToken=?', 'regEnabled=?', 'published=?'],
            [$token, '1', '1']
        );

        if ($event === null) {
            $template->error = 'invalid_token';

            return $template->getResponse();
        }

        // Check deadline
        if ($event->regDeadline && (int) $event->regDeadline < time()) {
            $template->error = 'deadline_passed';

            return $template->getResponse();
        }

        // Check max participants
        if ($event->regMaxParticipants) {
            $count = EventRegistration::countBy('pid', $event->id);
            if ($count >= (int) $event->regMaxParticipants) {
                $template->error = 'max_reached';

                return $template->getResponse();
            }
        }

        $formFields = $this->buildFormFields($event);
        $errors = [];
        $submitted = false;
        $postData = [];

        if ($request->isMethod('POST') && $request->request->get('event_reg_token') === $token) {
            [$errors, $submitted, $postData] = $this->processSubmission($request, $event, $formFields);
        }

        $template->event = $event;
        $template->formFields = $formFields;
        $template->errors = $errors;
        $template->submitted = $submitted;
        $template->token = $token;
        $template->postData = $postData;
        $template->formAction = $request->getUri();

        return $template->getResponse();
    }

    /**
     * Build a normalized array of form field descriptors from the event config.
     *
     * @return array<int, array{type:string, name:string, label:string, mandatory:bool, options:list<array{value:string, label:string}>}>
     */
    private function buildFormFields(CalendarEventsModel $event): array
    {
        if ($event->regFormType === 'existing' && $event->regFormId) {
            return $this->buildFieldsFromForm((int) $event->regFormId);
        }

        return $this->buildCustomFields($event);
    }

    private function buildFieldsFromForm(int $formId): array
    {
        $fields = FormFieldModel::findBy(
            ['pid=?', 'invisible!=?'],
            [$formId, '1'],
            ['order' => 'sorting ASC']
        );

        if ($fields === null) {
            return [];
        }

        $skipTypes = ['fieldsetStart', 'fieldsetStop', 'html', 'explanation', 'submit', 'captcha'];
        $result = [];

        foreach ($fields as $field) {
            if (in_array($field->type, $skipTypes, true)) {
                continue;
            }

            $options = [];

            if ($field->options) {
                foreach (StringUtil::deserialize($field->options, true) as $opt) {
                    $options[] = ['value' => (string) ($opt['value'] ?? ''), 'label' => (string) ($opt['label'] ?? '')];
                }
            }

            $result[] = [
                'type'      => $field->type,
                'name'      => $field->name,
                'label'     => $field->label,
                'mandatory' => (bool) $field->mandatory,
                'options'   => $options,
            ];
        }

        return $result;
    }

    private function buildCustomFields(CalendarEventsModel $event): array
    {
        $rawFields = StringUtil::deserialize($event->regCustomFields, true);
        $result = [];

        foreach ($rawFields as $f) {
            if (empty($f['fieldName'])) {
                continue;
            }

            $options = [];

            if (!empty($f['fieldOptions'])) {
                foreach (explode(',', $f['fieldOptions']) as $opt) {
                    $opt = trim($opt);
                    if ($opt !== '') {
                        $options[] = ['value' => $opt, 'label' => $opt];
                    }
                }
            }

            $result[] = [
                'type'      => $f['fieldType'] ?? 'text',
                'name'      => $f['fieldName'],
                'label'     => $f['fieldLabel'] ?? $f['fieldName'],
                'mandatory' => !empty($f['mandatory']),
                'options'   => $options,
            ];
        }

        return $result;
    }

    /**
     * @return array{0: array<string, string>, 1: bool, 2: array<string, string>}
     */
    private function processSubmission(Request $request, CalendarEventsModel $event, array $formFields): array
    {
        $errors = [];
        $data = [];

        foreach ($formFields as $field) {
            $value = trim((string) $request->request->get($field['name'], ''));

            if ($field['mandatory'] && $value === '') {
                $errors[$field['name']] = 'required';
            }

            if ($field['type'] === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field['name']] = 'invalid_email';
            }

            $data[$field['name']] = htmlspecialchars(strip_tags($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        if (!empty($errors)) {
            return [$errors, false, $data];
        }

        // Extract common submitter fields
        $name = $data['name'] ?? $data['vorname'] ?? $data['firstname'] ?? '';

        foreach (['lastname', 'nachname'] as $lastKey) {
            if (isset($data[$lastKey]) && $data[$lastKey] !== '') {
                $name = trim($name . ' ' . $data[$lastKey]);
                break;
            }
        }

        $email = $data['email'] ?? '';
        $phone = $data['phone'] ?? $data['telefon'] ?? $data['tel'] ?? $data['phone_number'] ?? '';

        $registration = new EventRegistration();
        $registration->pid = $event->id;
        $registration->tstamp = time();
        $registration->dateAdded = time();
        $registration->submitterName = trim($name);
        $registration->submitterEmail = $email;
        $registration->submitterPhone = $phone;
        $registration->data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $registration->ip = substr($request->getClientIp() ?? '', 0, 64);
        $registration->save();

        if ($event->regNotificationEmail) {
            $this->sendNotification($event, $data, $registration);
        }

        return [[], true, []];
    }

    private function sendNotification(CalendarEventsModel $event, array $data, EventRegistration $registration): void
    {
        try {
            $lines = [sprintf("Neue Anmeldung für: %s\n", $event->title)];

            foreach ($data as $key => $value) {
                $lines[] = sprintf('%s: %s', $key, $value);
            }

            $lines[] = '';
            $lines[] = sprintf('Anmeldezeitpunkt: %s', date('d.m.Y H:i', (int) $registration->dateAdded));
            $lines[] = sprintf('Anmeldungs-ID: %d', (int) $registration->id);

            $email = (new Email())
                ->to(...explode(',', $event->regNotificationEmail))
                ->subject(sprintf('Neue Eventanmeldung: %s', $event->title))
                ->text(implode("\n", $lines));

            $this->mailer->send($email);
        } catch (\Throwable) {
            // Do not let a mailer error abort the registration
        }
    }
}
