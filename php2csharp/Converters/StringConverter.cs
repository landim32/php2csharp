using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Text.RegularExpressions;
using System.Threading.Tasks;

namespace PHP2CSharp.Converters
{
    public class StringConverter : BaseConverter
    {
        private const string STRING_DECLARATION = @"\$([0-9,a-z,A-Z,_]+)\s*=\s*""(.*?)"";";
        private const string STRING_VAR = @"\$([0-9,a-z,A-Z,_]+)\s*=\s*(.*?);";

        public override string convert(string sourceCode)
        {
            sourceCode = Regex.Replace(sourceCode, STRING_DECLARATION, delegate (Match match) {
                return "var " + match.Groups[1].Value + " = \"" + match.Groups[2].Value + "\";";
            }, RegexOptions.IgnoreCase);
            /*
            sourceCode = Regex.Replace(sourceCode, STRING_VAR, delegate (Match match) {
                return "var " + match.Groups[1].Value + " = " + Regex.Replace(match.Groups[2].Value, @"(\.)", "+", RegexOptions.IgnoreCase) + ";";
                //return "var " + match.Groups[1].Value + " = \"" + match.Groups[2].Value + "\";";
            }, RegexOptions.IgnoreCase);
            */
            return sourceCode;
        }
    }
}
