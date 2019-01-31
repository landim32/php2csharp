using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Text.RegularExpressions;
using System.Threading.Tasks;

namespace PHP2CSharp.Converters
{
    public class ArrayConverter : BaseConverter
    {
        private const string REGEX_ARRAY = @"private\s*\$([0-9,a-z,A-Z,_]+)\s*=\s*array\(\);";
        private const string COUNT = @"count\((.*?)\)\s*([\!,<,>,\=]+)\s*(\d+)";
        private const string ARRAY_KEY_EXISTS = @"array_key_exists\((.*?),\s*\$([0-9,a-z,A-Z,_]+)\)";
        private const string LIST_ADD = @"\$([0-9,a-z,A-Z,_]+)\[\]\s*=\s*(.*?);";
        private const string DICTIONARY_ADD = @"\$([0-9,a-z,A-Z,_]+)\[(.*?)\]\s*=\s*(.*?);";

        public override string convert(string sourceCode)
        {
            sourceCode = Regex.Replace(sourceCode, REGEX_ARRAY, delegate (Match match) {
                return "private IList<object> " + match.Groups[1].Value + " = new List<object>();";
            }, RegexOptions.IgnoreCase);

            sourceCode = Regex.Replace(sourceCode, ARRAY_KEY_EXISTS, delegate (Match match) {
                return match.Groups[2].Value + ".ConstainsKey(" + match.Groups[1].Value + ")";
            }, RegexOptions.IgnoreCase);

            sourceCode = Regex.Replace(sourceCode, COUNT, delegate (Match match) {
                return match.Groups[1].Value + ".Count() " + match.Groups[2].Value + " " + match.Groups[3].Value;
            }, RegexOptions.IgnoreCase);

            sourceCode = Regex.Replace(sourceCode, LIST_ADD, delegate (Match match) {
                return match.Groups[1].Value + ".Add(" + match.Groups[2].Value + ");";
            }, RegexOptions.IgnoreCase);

            sourceCode = Regex.Replace(sourceCode, DICTIONARY_ADD, delegate (Match match) {
                return match.Groups[1].Value + ".Add(" + match.Groups[2].Value + ", " + match.Groups[3].Value + ");";
            }, RegexOptions.IgnoreCase);

            return sourceCode;
        }
    }
}
