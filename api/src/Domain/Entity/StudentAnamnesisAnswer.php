<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'student_anamnesis_answers')]
#[ORM\Index(name: 'idx_anamnesis_ans_session', columns: ['student_anamnesis_id'])]
#[ORM\Index(name: 'idx_anamnesis_ans_question', columns: ['anamnesis_question_id'])]
class StudentAnamnesisAnswer
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: StudentAnamnesis::class, inversedBy: 'answers')]
    #[ORM\JoinColumn(name: 'student_anamnesis_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private StudentAnamnesis $studentAnamnesis;

    #[ORM\ManyToOne(targetEntity: AnamnesisQuestion::class)]
    #[ORM\JoinColumn(name: 'anamnesis_question_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?AnamnesisQuestion $question = null;

    #[ORM\Column(type: 'text')]
    private string $questionTextSnapshot;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $answerValue = null;

    public function __construct(
        StudentAnamnesis $studentAnamnesis,
        string $questionTextSnapshot,
        ?AnamnesisQuestion $question = null,
        ?string $answerValue = null,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->studentAnamnesis = $studentAnamnesis;
        $this->questionTextSnapshot = $questionTextSnapshot;
        $this->question = $question;
        $this->answerValue = $answerValue;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getStudentAnamnesis(): StudentAnamnesis
    {
        return $this->studentAnamnesis;
    }

    public function getQuestion(): ?AnamnesisQuestion
    {
        return $this->question;
    }

    public function getQuestionTextSnapshot(): string
    {
        return $this->questionTextSnapshot;
    }

    public function getAnswerValue(): ?string
    {
        return $this->answerValue;
    }

    public function setAnswerValue(?string $answerValue): self
    {
        $this->answerValue = $answerValue;
        return $this;
    }
}
