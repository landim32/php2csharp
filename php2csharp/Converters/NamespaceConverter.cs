using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Text.RegularExpressions;
using System.Threading.Tasks;

namespace PHP2CSharp.Converters
{
    public class NamespaceConverter: BaseConverter
    {
        private const string REGEX_NAMESPACE = @"namespace ([a-z,A-Z,\\]+);";
        private const string REMOVE_USE = @"use\s*([0-9,a-z,A-Z,_,\\]+);";

        private string convertNamespace(string sourceCode) {
            var source = new StringBuilder();
            //source.AppendLine("using System;");
            //source.AppendLine("");
            var lines = sourceCode.Trim().Split(new[] { '\n' });
            bool hasNamespace = false;
            foreach (var line in lines)
            {
                if (string.IsNullOrEmpty(line))
                {
                    continue;
                }
                var currentLine = line;
                if (hasNamespace)
                {
                    currentLine = "    " + currentLine;
                }
                else
                {
                    var result = Regex.Match(line, REGEX_NAMESPACE);
                    if (result.Success)
                    {
                        hasNamespace = true;
                        var ns = result.Groups[1].Value.Replace('\\', '.');
                        currentLine = "namespace " + ns + " {";
                    }
                }
                source.AppendLine(currentLine);
            }
            if (hasNamespace)
            {
                source.AppendLine("}");
            }
            return source.ToString();
        }

        public override string convert(string sourceCode)
        {
            var source = new StringBuilder();
            source.AppendLine("using System;");
            sourceCode = Regex.Replace(sourceCode, REMOVE_USE, delegate (Match match) {
                source.AppendLine("using " + match.Groups[1].Value.Replace('\\', '.') + ";");
                return "";
            }, RegexOptions.IgnoreCase);
            source.AppendLine();
            source.AppendLine();
            source.Append(convertNamespace(sourceCode));
            return source.ToString();
        }
    }
}
