<?php

namespace App\Enums;

enum CodeLanguage: string
{
    case Python = 'python';
    case Php = 'php';
    case JavaScript = 'javascript';
    case Html = 'html';
    case Css = 'css';
    case Sql = 'sql';
    case Java = 'java';
    case Cpp = 'cpp';
    case CSharp = 'csharp';
    case Bash = 'bash';

    public function label(): string
    {
        return match ($this) {
            self::Python => 'Python',
            self::Php => 'PHP',
            self::JavaScript => 'JavaScript',
            self::Html => 'HTML',
            self::Css => 'CSS',
            self::Sql => 'SQL',
            self::Java => 'Java',
            self::Cpp => 'C++',
            self::CSharp => 'C#',
            self::Bash => 'Bash',
        };
    }
}
