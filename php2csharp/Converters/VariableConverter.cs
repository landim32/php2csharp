using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Text.RegularExpressions;
using System.Threading.Tasks;

namespace PHP2CSharp.Converters
{
    public class VariableConverter : BaseConverter
    {
        private const string VARIABLE_PROPERTY_GET = @"->get([0-9,a-z,A-Z,_]+)\(\)";
        private const string VARIABLE_PROPERTY_SET = @"\$([0-9,a-z,A-Z,_]+)->set([0-9,a-z,A-Z,_]+)\((.*?)\);";
        private const string VARIABLE_METHOD = @"->([0-9,a-z,A-Z,_]+)\((.*?)\)";
        private const string VARIABLE_ATTRIBUTE = @"->([0-9,a-z,A-Z,_]+)";
        private const string FOREACH_SIMPLE = @"foreach \s*\(\$(.*?)\s*as\s*\$([0-9,a-z,A-Z,_]+)\)";

        public override string convert(string sourceCode)
        {
            sourceCode = Regex.Replace(sourceCode, VARIABLE_PROPERTY_GET, delegate (Match match) {
                return "." + match.Groups[1].Value;
            }, RegexOptions.IgnoreCase);
            sourceCode = Regex.Replace(sourceCode, VARIABLE_PROPERTY_SET, delegate (Match match) {
                return match.Groups[1].Value + "." + match.Groups[2].Value + " = " + match.Groups[3].Value + ";";
            }, RegexOptions.IgnoreCase);
            sourceCode = Regex.Replace(sourceCode, VARIABLE_METHOD, delegate (Match match) {
                return "." + match.Groups[1].Value + "(" + match.Groups[2].Value + ")";
            }, RegexOptions.IgnoreCase);
            sourceCode = Regex.Replace(sourceCode, VARIABLE_ATTRIBUTE, delegate (Match match) {
                return "." + match.Groups[1].Value;
            }, RegexOptions.IgnoreCase);
            sourceCode = Regex.Replace(sourceCode, FOREACH_SIMPLE, delegate (Match match) {
                return "foreach (var " + match.Groups[2].Value + " in " + match.Groups[1].Value + ")";
            }, RegexOptions.IgnoreCase);
            return sourceCode;
        }
    }
}
